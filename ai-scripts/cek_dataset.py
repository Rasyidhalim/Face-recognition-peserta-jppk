import os
import cv2
import numpy as np
import urllib.request
import mediapipe as mp
from mediapipe.tasks import python
from mediapipe.tasks.python import vision

# Konfigurasi path
BASE_DIR = os.path.dirname(__file__)
LARAVEL_DATASET_PATH = os.path.abspath(os.path.join(BASE_DIR, '..', 'storage', 'app', 'dataset_wajah'))
MODEL_PATH = os.path.join(BASE_DIR, 'face_landmarker.task')

# Download model jika belum ada
if not os.path.exists(MODEL_PATH):
    print("Mendownload model AI Face Landmarker (sekitar 2MB)...")
    url = "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task"
    urllib.request.urlretrieve(url, MODEL_PATH)
    print("Download selesai!")

# Inisialisasi MediaPipe Tasks API (Versi 1.0.0+)
base_options = python.BaseOptions(model_asset_path=MODEL_PATH)
options = vision.FaceLandmarkerOptions(
    base_options=base_options,
    output_face_blendshapes=False,
    output_facial_transformation_matrixes=False,
    num_faces=1)

detector = vision.FaceLandmarker.create_from_options(options)

def check_dataset_quality(no_jppk):
    user_dir = os.path.join(LARAVEL_DATASET_PATH, no_jppk)
    
    if not os.path.exists(user_dir):
        print(f"Error: Folder dataset untuk {no_jppk} tidak ditemukan.")
        return
        
    stats = {
        "lurus": 0, "kiri": 0, "kanan": 0,
        "atas": 0, "bawah": 0,
        "wajah_tidak_terdeteksi": 0,
        "blur": 0, "total_gambar": 0
    }

    images = [f for f in os.listdir(user_dir) if f.lower().endswith(('.jpg', '.jpeg', '.png'))]
    stats["total_gambar"] = len(images)
    
    print(f"Menganalisis {len(images)} gambar untuk JPPK {no_jppk}...\n")
    
    for img_name in images:
        img_path = os.path.join(user_dir, img_name)
        img = cv2.imread(img_path)
        if img is None: continue
            
        # 1. Pengecekan Blur (Menggunakan Laplacian Variance)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        blur_score = cv2.Laplacian(gray, cv2.CV_64F).var()
        if blur_score < 100:
            stats["blur"] += 1
            
        # 2. Pengecekan Arah Kepala
        rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_img)
        results = detector.detect(mp_image)
        
        if not results.face_landmarks:
            stats["wajah_tidak_terdeteksi"] += 1
            continue
            
        face_landmarks = results.face_landmarks[0]
        img_h, img_w, _ = img.shape
        face_3d = []
        face_2d = []
        
        # Titik hidung(1), dagu(199), mata kiri(33), mata kanan(263), bibir kiri(61), bibir kanan(291)
        key_points = [33, 263, 1, 61, 291, 199]
        
        for idx in key_points:
            lm = face_landmarks[idx]
            x, y = int(lm.x * img_w), int(lm.y * img_h)
            face_2d.append([x, y])
            face_3d.append([x, y, lm.z])
                
        face_2d = np.array(face_2d, dtype=np.float64)
        face_3d = np.array(face_3d, dtype=np.float64)
        
        focal_length = 1 * img_w
        cam_matrix = np.array([[focal_length, 0, img_h / 2],
                               [0, focal_length, img_w / 2],
                               [0, 0, 1]])
        dist_matrix = np.zeros((4, 1), dtype=np.float64)
        
        success, rot_vec, trans_vec = cv2.solvePnP(face_3d, face_2d, cam_matrix, dist_matrix)
        rmat, jac = cv2.Rodrigues(rot_vec)
        angles, mtxR, mtxQ, Qx, Qy, Qz = cv2.RQDecomp3x3(rmat)
        
        pitch = angles[0] * 360
        yaw = angles[1] * 360
        
        if yaw < -10:
            stats["kiri"] += 1
        elif yaw > 10:
            stats["kanan"] += 1
        elif pitch < -10:
            stats["bawah"] += 1
        elif pitch > 10:
            stats["atas"] += 1
        else:
            stats["lurus"] += 1

    print("=== HASIL ANALISIS DATA TRAINING ===")
    print(f"Total Gambar    : {stats['total_gambar']}")
    print(f"- Wajah Lurus   : {stats['lurus']}")
    print(f"- Menghadap Kiri: {stats['kiri']}")
    print(f"- Menghadap Kanan:{stats['kanan']}")
    print(f"- Menghadap Atas: {stats['atas']}")
    print(f"- Menghadap Bawah:{stats['bawah']}")
    print(f"- Tidak Terbaca : {stats['wajah_tidak_terdeteksi']}")
    print("------------------------------------")
    print(f"- Gambar Blur   : {stats['blur']} (dari total {stats['total_gambar']})")
    print("------------------------------------")
    print("ℹ️  INFO SUPER DNA:")
    print(f"Dari {stats['total_gambar']} gambar di atas, 30 gambar teratas")
    print("digabungkan (Mean) oleh model AI FaceNet menjadi")
    print("1 matriks 'Super DNA' 512-dimensi yang sangat akurat.")
    print("====================================")

if __name__ == "__main__":
    NO_JPPK_UJI = input("Masukkan Nomor JPPK pasien yang ingin dievaluasi: ")
    check_dataset_quality(NO_JPPK_UJI)
