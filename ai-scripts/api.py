import os
import cv2
import numpy as np
import mysql.connector
import uuid
import logging
import json
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from ultralytics import YOLO
from deepface import DeepFace

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
                        ref_data = DeepFace.represent(img_path=first_frame_path, model_name="Facenet", enforce_detection=False)
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

        for r in results:
            for box in r.boxes:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                
                h, w, _ = frame.shape
                crop_y1, crop_y2 = max(0, y1-30), min(h, y2+30)
                crop_x1, crop_x2 = max(0, x1-30), min(w, x2+30)

                crop_img = frame[crop_y1:crop_y2, crop_x1:crop_x2]
                if crop_img.size == 0: continue

                label = "TIDAK DIKENAL"
                best_score = 0.0
                info_db = None 

                try:
                    realtime_data = DeepFace.represent(img_path=crop_img, model_name="Facenet", enforce_detection=False)
                    if realtime_data:
                        realtime_embedding = realtime_data[0]["embedding"]
                        
                        for known_jppk, ref_emb in known_embeddings.items():
                            score = cosine_similarity(ref_emb, realtime_embedding)
                            
                            if score > best_score:
                                best_score = score
                                if score > 0.65: 
                                    label = known_jppk 
                                    info_db = karyawan_info.get(label, None)
                except Exception:
                    pass 

                detections.append({
                    "label": label, 
                    "confidence": float(best_score) if label != "TIDAK DIKENAL" else 0.0,
                    "box": [x1, y1, x2, y2],
                    "info_pindad": info_db 
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
        saved_count = 0
        embeddings_list = [] 
        
        while cap.isOpened():
            ret, frame = cap.read()
            if not ret:
                break 
                
            results = model(frame, classes=[0], conf=0.6, verbose=False)
            face_saved_in_this_frame = False
            
            for r in results:
                for box in r.boxes:
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    h, w, _ = frame.shape
                    
                    crop_y1 = max(0, y1 - 30)
                    crop_y2 = min(h, y2 + 30)
                    crop_x1 = max(0, x1 - 30)
                    crop_x2 = min(w, x2 + 30)

                    crop_img = frame[crop_y1:crop_y2, crop_x1:crop_x2]
                    
                    if crop_img.size > 0:
                        img_path = os.path.join(user_dir, f"frame_{saved_count + 1:03d}.jpg")
                        cv2.imwrite(img_path, crop_img)
                        saved_count += 1
                        face_saved_in_this_frame = True
                        
                        if len(embeddings_list) < 30:
                            try:
                                ref_data = DeepFace.represent(img_path=crop_img, model_name="Facenet", enforce_detection=False)
                                if ref_data:
                                    embeddings_list.append(ref_data[0]["embedding"])
                            except Exception:
                                pass
                                
                        break 
                        
                if face_saved_in_this_frame:
                    break
                    
            if saved_count >= 200:
                break
                
        cap.release()
        
        if saved_count == 0 or len(embeddings_list) == 0:
            raise HTTPException(status_code=400, detail="Wajah tidak terdeteksi oleh AI. Mohon posisikan wajah tepat di depan kamera dengan pencahayaan terang.")
            
        super_dna = np.mean(embeddings_list, axis=0)
        super_dna = np.nan_to_num(super_dna, nan=0.0, posinf=1.0, neginf=-1.0)
        clean_embedding = [float(x) for x in super_dna]
        
        # Suntikkan langsung ke RAM internal agar Kiosk langsung mengenali tanpa restart
        known_embeddings[no_jppk] = clean_embedding
        
        return {
            "status": "success",
            "message": f"Berhasil mengekstrak {saved_count} frame gambar dan mengunci koordinat biometrik.",
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