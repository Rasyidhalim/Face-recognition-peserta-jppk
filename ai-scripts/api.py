import os
import cv2
import numpy as np
import mysql.connector
import uuid
import logging
import json
import math
import time

liveness_status = {}

def calculate_distance(p1, p2):
    return math.hypot(p2[0] - p1[0], p2[1] - p1[1])

def eye_aspect_ratio(eye_landmarks, img_w, img_h):
    v1 = calculate_distance(eye_landmarks[1], eye_landmarks[5])
    v2 = calculate_distance(eye_landmarks[2], eye_landmarks[4])
    h = calculate_distance(eye_landmarks[0], eye_landmarks[3])
    ear = (v1 + v2) / (2.0 * h) if h > 0 else 0
    return ear

def is_blinking(face_landmarks, img_w, img_h):
    LEFT_EYE_INDICES = [33, 160, 158, 133, 153, 144]
    RIGHT_EYE_INDICES = [362, 385, 387, 263, 373, 380]
    
    left_eye = [(face_landmarks[i].x * img_w, face_landmarks[i].y * img_h) for i in LEFT_EYE_INDICES]
    right_eye = [(face_landmarks[i].x * img_w, face_landmarks[i].y * img_h) for i in RIGHT_EYE_INDICES]
    
    left_ear = eye_aspect_ratio(left_eye, img_w, img_h)
    right_ear = eye_aspect_ratio(right_eye, img_w, img_h)
    average_ear = (left_ear + right_ear) / 2.0
    print(f"DEBUG: EAR terdeteksi: {average_ear:.3f}")
    # Ubah threshold dari 0.25 ke 0.28 agar kedipan cepat (ngedip) langsung terdeteksi
    return average_ear < 0.28
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from ultralytics import YOLO
from deepface import DeepFace
import urllib.request
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision as mp_vision

MODEL_PATH = os.path.join(os.path.dirname(__file__), 'face_landmarker.task')
if not os.path.exists(MODEL_PATH):
    urllib.request.urlretrieve("https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task", MODEL_PATH)

base_options = mp_python.BaseOptions(model_asset_path=MODEL_PATH)
options = mp_vision.FaceLandmarkerOptions(
    base_options=base_options,
    output_face_blendshapes=False,
    output_facial_transformation_matrixes=False,
    num_faces=1)
face_landmarker = mp_vision.FaceLandmarker.create_from_options(options)

# Matikan log TensorFlow agar terminal bersih
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 
logging.getLogger('tensorflow').setLevel(logging.FATAL)

app = FastAPI()

# Pengaturan CORS untuk Vue.js (Di kiosk rs pindad) - TETAP DIPERTAHANKAN WAIJB
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- 1. INISIALISASI MODEL ---
print("=========================================")
print("[INFO] JPPK RS PINDAD AI SERVER - STARTING")
print("[INFO] Memuat AI YOLOv8 Person Detector...")
model = YOLO('yolov8n.pt') 

# --- 2. LOAD DATABASE & KONFIGURASI ---
LARAVEL_DATASET_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'dataset_wajah'))
os.makedirs(LARAVEL_DATASET_PATH, exist_ok=True)

known_embeddings = {} 
karyawan_info = {}    

def cosine_similarity(a, b):
    return np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b))

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '', # Sesuaikan password root MySQL Anda jika ada
    'database': 'rs' # DB Laravel Anda
}

def load_all_data_to_ram():
    print("[INFO] Menyambungkan ke Database MySQL Laravel...")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        # 🔥 MODIFIKASI: Tarik juga kolom face_embedding dari database
        query = """
            SELECT p.no_jppk, p.nama_peserta, p.face_embedding, u.nama_unit, pl.nama_plan, p.tgl_lahir
            FROM peserta_jppk p
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN plans pl ON p.plan_id = pl.id
        """
        cursor.execute(query)
        
        db_embedding_count = 0
        karyawan_count = 0
        
        for row in cursor.fetchall():
            no_jppk = row['no_jppk']
            karyawan_info[no_jppk] = {
                "nama_peserta": row['nama_peserta'],
                "nama_unit": row['nama_unit'] if row['nama_unit'] else "Belum Ada Unit",
                "nama_plan": row['nama_plan'] if row['nama_plan'] else "Belum Ada Plan",
                "tgl_lahir": row['tgl_lahir'].strftime('%d-%m-%Y') if row['tgl_lahir'] else '-'
            }
            karyawan_count += 1
            
            # 🔥 OLEH-OLEH BARU: Jika data DNA wajah sudah ada di DB, langsung sedot ke RAM tanpa olah gambar!
            if row['face_embedding']:
                try:
                    embedding_data = row['face_embedding']
                    if isinstance(embedding_data, str):
                        embedding_data = json.loads(embedding_data)
                    known_embeddings[no_jppk] = embedding_data
                    db_embedding_count += 1
                except Exception as e:
                    print(f"  -> [WARNING] Gagal membaca koordinat DB untuk {no_jppk}: {e}")
        
        conn.close()
        print(f"  -> Data JPPK MySQL berhasil dimuat ({karyawan_count} info karyawan).")
        print(f"  -> Berhasil menyedot {db_embedding_count} DNA Wajah LANGSUNG dari Database MySQL ke RAM.")
    except Exception as e:
        print(f"  -> [WARNING] Gagal koneksi/muat MySQL Laravel: {e}")

    # --- FALLBACK SYSTEM LAMA (DIANTISIPASI JIKA ADA DATA LOKAL YANG BELUM MASUK KE MYSQL) ---
    print(f"[INFO] Memeriksa sisa dataset di folder lokal: {LARAVEL_DATASET_PATH}")
    if os.path.exists(LARAVEL_DATASET_PATH):
        extracted_count = 0
        
        for item in os.listdir(LARAVEL_DATASET_PATH):
            item_path = os.path.join(LARAVEL_DATASET_PATH, item)
            
            if os.path.isdir(item_path):
                no_jppk = item
                # Jika sudah disedot dari DB, lewati! Biar hemat CPU
                if no_jppk in known_embeddings:
                    continue
                
                frames = [f for f in os.listdir(item_path) if f.lower().endswith(('.jpg', '.jpeg'))]
                if frames:
                    frames.sort() 
                    first_frame_path = os.path.join(item_path, frames[0])
                    try:
                        ref_data = DeepFace.represent(img_path=first_frame_path, model_name="Facenet", enforce_detection=True)
                        if ref_data:
                            known_embeddings[no_jppk] = ref_data[0]["embedding"]
                            extracted_count += 1
                    except Exception as e:
                        print(f"  -> [WARNING] DeepFace gagal memuat foto referensi lokal dari {no_jppk}: {e}")
            
            elif item.lower().endswith('.npy'):
                no_jppk = os.path.splitext(item)[0]
                if no_jppk in known_embeddings:
                    continue
                try:
                    known_embeddings[no_jppk] = np.load(item_path)
                    extracted_count += 1
                except Exception as e:
                    print(f"  -> [WARNING] Gagal memuat file DNA lokal {item}: {e}")

        if extracted_count > 0:
            print(f"  -> Tambahan {extracted_count} DNA Wajah dari folder lokal berhasil di-cache ke RAM.")
    else:
        print(f"[ERROR] Folder dataset Laravel tidak ditemukan!")
    print(f"[INFO] TOTAL DATA BIOMETRIK SIAP DI RAM: {len(known_embeddings)} PASIEN.")
    print("=========================================")

load_all_data_to_ram()


# --- 3. JALUR API UNTUK KIOSK (Recognize - Realtime) ---
@app.post("/recognize")
async def recognize_face(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        nparr = np.frombuffer(contents, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if frame is None:
            return {"status": "error", "message": "File gambar rusak"}

        detections = []
        results = model(frame, classes=[0], conf=0.7, verbose=False)

        # Cari kotak paling besar (orang yang paling dekat dengan kamera)
        largest_box = None
        max_area = 0
        
        for r in results:
            for box in r.boxes:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                area = (x2 - x1) * (y2 - y1)
                if area > max_area:
                    max_area = area
                    largest_box = box

        if largest_box is not None:
            x1, y1, x2, y2 = map(int, largest_box.xyxy[0])
            
            # FILTER: Tetap abaikan jika orang terbesar pun terlalu kecil/jauh (lebar < 150px)
            if (x2 - x1) >= 150 and (y2 - y1) >= 150:
                h, w, _ = frame.shape
                crop_y1, crop_y2 = max(0, y1-30), min(h, y2+30)
                crop_x1, crop_x2 = max(0, x1-30), min(w, x2+30)

                crop_img = frame[crop_y1:crop_y2, crop_x1:crop_x2]
                
                if crop_img.size > 0:
                    label = "TIDAK DIKENAL"
                    best_score = 0.0
                    info_db = None 

                    try:
                        # WAJIB: enforce_detection=True agar tidak menebak random dari gambar baju/badan
                        realtime_data = DeepFace.represent(img_path=crop_img, model_name="Facenet", enforce_detection=True)
                        if realtime_data:
                            realtime_embedding = realtime_data[0]["embedding"]
                            
                            # Ambil koordinat wajah yang BENAR-BENAR wajah (di dalam kotak badan)
                            facial_area = realtime_data[0].get("facial_area")
                            if facial_area:
                                # Ubah x1, y1, x2, y2 menjadi kotak WAJAH, bukan kotak badan
                                face_x = facial_area.get("x", 0)
                                face_y = facial_area.get("y", 0)
                                face_w = facial_area.get("w", 0)
                                face_h = facial_area.get("h", 0)
                                
                                # Tambahkan padding agar kotak melingkupi seluruh kepala
                                pad_x = int(face_w * 0.2)
                                pad_y = int(face_h * 0.25)
                                
                                x1 = max(0, crop_x1 + face_x - pad_x)
                                y1 = max(0, crop_y1 + face_y - pad_y)
                                x2 = min(w, crop_x1 + face_x + face_w + pad_x)
                                y2 = min(h, crop_y1 + face_y + face_h + pad_y)
                            
                            for known_jppk, ref_emb in known_embeddings.items():
                                score = cosine_similarity(ref_emb, realtime_embedding)
                                
                                if score > best_score:
                                    best_score = score
                                    # Ubah threshold menjadi 0.70 sesuai permintaan
                                    if score > 0.70: 
                                        label = known_jppk 
                                        info_db = karyawan_info.get(label, None)
                    except ValueError:
                        # Abaikan secara diam-diam jika wajah tidak ditemukan (misal orang membelakangi kamera)
                        pass
                    except Exception as e:
                        print(f"DEBUG: DeepFace error for test image: {e}") 

                    is_lively = False
                    if label != "TIDAK DIKENAL":
                        rgb_img = cv2.cvtColor(crop_img, cv2.COLOR_BGR2RGB)
                        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_img)
                        mp_results = face_landmarker.detect(mp_image)
                        
                        if mp_results.face_landmarks:
                            if is_blinking(mp_results.face_landmarks[0], crop_img.shape[1], crop_img.shape[0]):
                                liveness_status[label] = time.time()
                                
                        if label in liveness_status:
                            if time.time() - liveness_status[label] < 15:
                                is_lively = True

                    detections.append({
                        "label": label, 
                        "confidence": float(best_score),
                        "box": [x1, y1, x2, y2], # Sekarang ini adalah kotak WAJAH
                        "info_pindad": info_db,
                        "is_lively": is_lively
                    })

        return {"status": "success", "data": detections}
    except Exception as e:
        return {"status": "error", "message": f"Server Error: {str(e)}"}


# --- JALUR UTAMA: EKSTRAKSI VIDEO MENJADI 200 FRAME GAMBAR + KIRIM EMBEDDING KE LARAVEL ---
@app.post("/extract-dna/{no_jppk}")
async def extract_faces_from_video(no_jppk: str, video: UploadFile = File(...)):
    user_dir = os.path.join(LARAVEL_DATASET_PATH, no_jppk)
    os.makedirs(user_dir, exist_ok=True)
    
    temp_video_path = f"temp_register_{uuid.uuid4().hex}.webm"
    with open(temp_video_path, "wb") as buffer:
        buffer.write(await video.read())
        
    try:
        cap = cv2.VideoCapture(temp_video_path)
        counts = {"lurus": 0, "kiri": 0, "kanan": 0, "atas": 0, "bawah": 0}
        saved_count = 0
        loop_counter = 0
        embeddings_list = [] 
        frame_idx = 0
        
        while saved_count < 200 and loop_counter < 3:
            ret, frame = cap.read()
            if not ret:
                cap.set(cv2.CAP_PROP_POS_FRAMES, 0)
                loop_counter += 1
                ret, frame = cap.read()
                if not ret: break 
                
            results = model(frame, classes=[0], conf=0.6, verbose=False)
            face_saved_in_this_frame = False
            
            for r in results:
                for box in r.boxes:
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    h, w, _ = frame.shape
                    
                    crop_y1, crop_y2 = max(0, y1 - 30), min(h, y2 + 30)
                    crop_x1, crop_x2 = max(0, x1 - 30), min(w, x2 + 30)
                    crop_img = frame[crop_y1:crop_y2, crop_x1:crop_x2]
                    
                    if crop_img.size > 0:
                        rgb_img = cv2.cvtColor(crop_img, cv2.COLOR_BGR2RGB)
                        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_img)
                        mp_results = face_landmarker.detect(mp_image)
                        
                        if mp_results.face_landmarks:
                            face_landmarks = mp_results.face_landmarks[0]
                            img_h, img_w, _ = crop_img.shape
                            face_3d, face_2d = [], []
                            for idx in [33, 263, 1, 61, 291, 199]:
                                lm = face_landmarks[idx]
                                x, y = int(lm.x * img_w), int(lm.y * img_h)
                                face_2d.append([x, y])
                                face_3d.append([x, y, lm.z])
                                
                            face_2d = np.array(face_2d, dtype=np.float64)
                            face_3d = np.array(face_3d, dtype=np.float64)
                            cam_matrix = np.array([[img_w, 0, img_h / 2], [0, img_w, img_w / 2], [0, 0, 1]])
                            dist_matrix = np.zeros((4, 1), dtype=np.float64)
                            _, rot_vec, _ = cv2.solvePnP(face_3d, face_2d, cam_matrix, dist_matrix)
                            rmat, _ = cv2.Rodrigues(rot_vec)
                            angles, _, _, _, _, _ = cv2.RQDecomp3x3(rmat)
                            
                            pitch, yaw = angles[0] * 360, angles[1] * 360
                            
                            arah = "lurus"
                            if yaw < -10: arah = "kiri"
                            elif yaw > 10: arah = "kanan"
                            elif pitch < -10: arah = "bawah"
                            elif pitch > 10: arah = "atas"
                            
                            # BEBAS KUOTA: Langsung simpan frame apapun pose-nya
                            img_path = os.path.join(user_dir, f"frame_{saved_count + 1:03d}.jpg")
                            cv2.imwrite(img_path, crop_img)
                            counts[arah] += 1
                            saved_count += 1
                            face_saved_in_this_frame = True
                            
                            if len(embeddings_list) < 200:
                                try:
                                    ref_data = DeepFace.represent(img_path=crop_img, model_name="Facenet", enforce_detection=True)
                                    if ref_data: embeddings_list.append(ref_data[0]["embedding"])
                                except: pass
                        break 
                if face_saved_in_this_frame:
                    break
                    
            if saved_count >= 200:
                break
                
        cap.release()
        
        # WAJIB 200 GAMBAR: Berapa pun pembagian arahnya, total keseluruhan WAJIB 200
        if saved_count < 200:
            msg = f"Gagal. Wajah terlalu sering keluar layar. Hanya terkumpul {saved_count} dari syarat mutlak 200 gambar. Lurus:{counts['lurus']}, Kiri:{counts['kiri']}, Kanan:{counts['kanan']}, Atas:{counts['atas']}, Bawah:{counts['bawah']}."
            raise HTTPException(status_code=400, detail=msg)
            
        super_dna = np.mean(embeddings_list, axis=0)
        super_dna = np.nan_to_num(super_dna, nan=0.0, posinf=1.0, neginf=-1.0)
        clean_embedding = [float(x) for x in super_dna]
        
        # Suntikkan langsung ke RAM internal agar Kiosk langsung mengenali tanpa restart
        known_embeddings[no_jppk] = clean_embedding
        
        return {
            "status": "success",
            "message": f"Berhasil menganalisis {saved_count} frame gambar dan mengunci koordinat biometrik.",
            "folder_path": f"dataset_wajah/{no_jppk}",
            "face_image_path": f"dataset_wajah/{no_jppk}/frame_001.jpg",
            "total_frames": saved_count,
            "embedding": clean_embedding,       
            "face_embedding": clean_embedding   
        }
        
    except HTTPException as he:
        raise he
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Gagal memproses registrasi video: {str(e)}")
    finally:
        if os.path.exists(temp_video_path):
            os.remove(temp_video_path)


# --- 4. API UNTUK SEEDER LARAVEL (Single Image Legacy) ---
@app.post("/generate-embedding")
async def generate_embedding(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        nparr = np.frombuffer(contents, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if frame is None:
            return {"status": "error", "message": "File gambar referensi JPPK rusak"}

        try:
            embedding_objs = DeepFace.represent(img_path=frame, model_name="Facenet", enforce_detection=True)
            if not embedding_objs:
                return {"status": "error", "message": "Gagal mengekstrak DNA wajah."}
            
            clean_emb = [float(x) for x in embedding_objs[0]["embedding"]]
            return {
                "status": "success",
                "embedding": clean_emb,
                "face_embedding": clean_emb
            }
        except ValueError:
            return {"status": "error", "message": "Wajah tidak terdeteksi jelas dalam foto pendaftaran."}
    except Exception as e:
        return {"status": "error", "message": f"Server Error generator: {str(e)}"}


# --- 5. API UNTUK RELOAD DATA (Auto Sinkron dari Laravel) ---
@app.post("/reload-data")
async def reload_data():
    global known_embeddings, karyawan_info
    known_embeddings.clear()
    karyawan_info.clear()
    load_all_data_to_ram()
    return {"status": "success", "message": "Data JPPK & DNA Wajah berhasil dimuat ulang ke RAM."}