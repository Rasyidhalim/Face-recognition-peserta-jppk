import os
import cv2
import numpy as np
import mysql.connector
from fastapi import FastAPI, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from ultralytics import YOLO
from deepface import DeepFace

# Matikan log TensorFlow agar terminal bersih
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 
import logging
logging.getLogger('tensorflow').setLevel(logging.FATAL)

app = FastAPI()

# Pengaturan CORS untuk Vue.js (Di kiosk rs pindad)
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
# Path absolut folder storage Laravel (Gunakan r di depan agar aman di Windows)
LARAVEL_DATASET_PATH = r"D:\SKRIPSI\jppk-rs-pindad\storage\app\dataset_wajah"

known_embeddings = {} 
karyawan_info = {}    

# Fungsi matematika kemiripan (Sangat Cepat)
def cosine_similarity(a, b):
    return np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b))

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '', # Sesuaikan password root Anda
    'database': 'rs' # DB Laravel
}

def load_all_data_to_ram():
    print("[INFO] Menyambungkan ke Database MySQL Laravel...")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        # Menggunakan LEFT JOIN agar jika unit/plan kosong, data pasien tetap masuk
        query = """
            SELECT p.no_jppk, p.nama_peserta, u.nama_unit, pl.nama_plan, p.tgl_lahir
            FROM peserta_jppk p
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN plans pl ON p.plan_id = pl.id
        """
        cursor.execute(query)
        
        count = 0
        for row in cursor.fetchall():
            no_jppk = row['no_jppk']
            karyawan_info[no_jppk] = {
                "nama_peserta": row['nama_peserta'],
                "nama_unit": row['nama_unit'] if row['nama_unit'] else "Belum Ada Unit",
                "nama_plan": row['nama_plan'] if row['nama_plan'] else "Belum Ada Plan",
                "tgl_lahir": row['tgl_lahir'].strftime('%d-%m-%Y') if row['tgl_lahir'] else '-'
            }
            count += 1
        
        conn.close()
        print(f"  -> Data JPPK MySQL berhasil dimuat ke RAM ({count} data).")
    except Exception as e:
        print(f"  -> [WARNING] Gagal koneksi/muat MySQL Laravel: {e}")

    print(f"[INFO] Mengekstrak DNA wajah (Embedding) dari folder Laravel...")
    if os.path.exists(LARAVEL_DATASET_PATH):
        extracted_count = 0
        for filename in os.listdir(LARAVEL_DATASET_PATH):
            if filename.lower().endswith(('.jpg', '.jpeg')):
                no_jppk = os.path.splitext(filename)[0] 
                img_p = os.path.join(LARAVEL_DATASET_PATH, filename)
                
                try:
                    ref_data = DeepFace.represent(img_path=img_p, model_name="Facenet", enforce_detection=False)
                    if ref_data:
                        known_embeddings[no_jppk] = ref_data[0]["embedding"]
                        extracted_count += 1
                except Exception as e:
                    print(f"  -> [WARNING] DeepFace gagal memuat {filename}: {e}")
        print(f"  -> Total {extracted_count} DNA Wajah berhasil di-cache ke RAM.")
    else:
        print(f"[ERROR] Folder dataset Laravel tidak ditemukan di:\n        {LARAVEL_DATASET_PATH}")
        print("        Recognition Kiosk akan gagal total.")
    print("=========================================")

# Jalankan proses memuat ke RAM saat startup server
load_all_data_to_ram()


# --- 3. JALUR API UNTUK KIOSK (Recognize - Realtime) ---
@app.post("/recognize")
async def recognize_face(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        nparr = np.fromstring(contents, np.uint8)
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


# --- 4. API UNTUK SEEDER LARAVEL ---
@app.post("/generate-embedding")
async def generate_embedding(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        nparr = np.fromstring(contents, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if frame is None:
            return {"status": "error", "message": "File gambar referensi JPPK rusak"}

        try:
            embedding_objs = DeepFace.represent(img_path=frame, model_name="Facenet", enforce_detection=True)
            
            if not embedding_objs:
                return {"status": "error", "message": "Gagal mengekstrak DNA wajah."}
            
            return {
                "status": "success",
                "embedding": embedding_objs[0]["embedding"]
            }

        except ValueError:
            return {"status": "error", "message": "Wajah tidak terdeteksi jelas dalam foto pendaftaran."}

    except Exception as e:
        return {"status": "error", "message": f"Server Error generator: {str(e)}"}


# --- 5. API UNTUK RELOAD DATA (Dipanggil via Laravel) ---
@app.post("/reload-data")
async def reload_data():
    global known_embeddings, karyawan_info
    # Bersihkan memory lama, muat memory baru
    known_embeddings.clear()
    karyawan_info.clear()
    load_all_data_to_ram()
    return {"status": "success", "message": "Data JPPK & DNA Wajah berhasil dimuat ulang ke RAM."}