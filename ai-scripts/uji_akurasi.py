import os
import cv2
import numpy as np
import requests
import warnings
import glob
from sklearn.metrics import classification_report, confusion_matrix

warnings.filterwarnings('ignore')

# ==========================================
# KONFIGURASI PENGUJIAN
# ==========================================
API_URL = "http://localhost:8001/recognize"
TEST_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), 'dataset_uji'))
DIR_TERDAFTAR = os.path.join(TEST_DIR, "terdaftar")
DIR_TIDAK_TERDAFTAR = os.path.join(TEST_DIR, "tidak_terdaftar")

def adjust_gamma(image, gamma=1.0):
    """Mensimulasikan pencahayaan rendah (low light)."""
    invGamma = 1.0 / gamma
    table = np.array([((i / 255.0) ** invGamma) * 255 for i in np.arange(0, 256)]).astype("uint8")
    return cv2.LUT(image, table)

def test_image_api(image_bytes):
    """Mengirim gambar ke API dan mengembalikan labelnya."""
    try:
        files = {'image': ('test.jpg', image_bytes, 'image/jpeg')}
        response = requests.post(API_URL, files=files)
        
        if response.status_code == 200:
            result = response.json()
            if result.get("status") == "success" and result.get("data"):
                best_match = max(result["data"], key=lambda x: x.get("confidence", 0))
                detected_label = best_match.get("label", "TIDAK DIKENAL")
                return detected_label
            else:
                return "TIDAK DIKENAL"
        else:
            return "ERROR_API"
    except Exception as e:
        return "ERROR_API"

def process_condition(images_terdaftar, images_tidak_terdaftar, condition="visible"):
    y_true = []
    y_pred = []
    
    tp = 0 # True Positive: Terdaftar dan dikenali benar
    fn = 0 # False Negative: Terdaftar tapi gagal dikenali/salah orang
    fp = 0 # False Positive: Orang asing dikenali sebagai terdaftar
    tn = 0 # True Negative: Orang asing ditolak (Tidak dikenal)
    
    # 1. UJI TERDAFTAR
    for img_path in images_terdaftar:
        expected_label = os.path.basename(os.path.dirname(img_path))
        original_frame = cv2.imread(img_path)
        if original_frame is None:
            continue
            
        frame_to_test = original_frame
        if condition == "low_light":
            frame_to_test = adjust_gamma(original_frame, gamma=0.3)
            
        _, buffer = cv2.imencode('.jpg', frame_to_test)
        detected_label = test_image_api(buffer.tobytes())
        
        y_true.append(expected_label)
        if detected_label == "TIDAK DIKENAL" or detected_label == "ERROR_API":
            y_pred.append("Tidak dikenal")
            fn += 1
        elif detected_label != expected_label:
            y_pred.append(detected_label)
            fn += 1 # Terdaftar tapi salah dikenali sebagai orang lain
        else:
            y_pred.append(detected_label)
            tp += 1

    # 2. UJI TIDAK TERDAFTAR (ASING)
    for img_path in images_tidak_terdaftar:
        original_frame = cv2.imread(img_path)
        if original_frame is None:
            continue
            
        frame_to_test = original_frame
        if condition == "low_light":
            frame_to_test = adjust_gamma(original_frame, gamma=0.3)
            
        _, buffer = cv2.imencode('.jpg', frame_to_test)
        detected_label = test_image_api(buffer.tobytes())
        
        y_true.append("Tidak dikenal")
        if detected_label == "TIDAK DIKENAL" or detected_label == "ERROR_API":
            y_pred.append("Tidak dikenal")
            tn += 1
        else:
            y_pred.append(detected_label)
            fp += 1
            
    return y_true, y_pred, tp, fp, tn, fn

def run_evaluation():
    print("Memulai Pengujian Akurasi (Dataset Uji Khusus)...")
    
    terdaftar_images = glob.glob(os.path.join(DIR_TERDAFTAR, "*", "*.[jp][pn]*[g]")) 
    tidak_terdaftar_images = glob.glob(os.path.join(DIR_TIDAK_TERDAFTAR, "*.[jp][pn]*[g]"))
    
    total_images = len(terdaftar_images) + len(tidak_terdaftar_images)
    if total_images == 0:
        print(f"[!] Dataset pengujian kosong di {TEST_DIR}")
        return
        
    print(f"Total gambar yang akan diuji: {total_images}")
    print("  -> Kondisi Visible Light sedang berjalan...")
    y_true_vis, y_pred_vis, tp_vis, fp_vis, tn_vis, fn_vis = process_condition(terdaftar_images, tidak_terdaftar_images, condition="visible")
    
    print("  -> Kondisi Low Light sedang berjalan...")
    y_true_low, y_pred_low, tp_low, fp_low, tn_low, fn_low = process_condition(terdaftar_images, tidak_terdaftar_images, condition="low_light")
    
    # Ambil semua label unik untuk urutan confusion matrix sklearn (Detail per kelas)
    all_labels = sorted(list(set(y_true_vis + y_pred_vis + y_pred_low)))
    if "Tidak dikenal" not in all_labels:
        all_labels.append("Tidak dikenal")
        
    def print_metrics(kondisi, tp, fp, tn, fn, y_true, y_pred):
        print("\n" + "="*60)
        print(f" HASIL PENGUJIAN AKURASI {kondisi.upper()} ")
        print("="*60)
        print("--- CONFUSION MATRIX (BINARY / SISTEM AKSES) ---")
        print(f"True Positive (TP)  : {tp} (Terdaftar & Dikenali Benar)")
        print(f"False Positive (FP) : {fp} (Orang Asing Dikenali Sebagai Terdaftar)")
        print(f"True Negative (TN)  : {tn} (Orang Asing Ditolak / Tidak Dikenal)")
        print(f"False Negative (FN) : {fn} (Terdaftar Tapi Gagal Dikenali / Salah Orang)")
        
        total = tp + fp + tn + fn
        akurasi = (tp + tn) / total if total > 0 else 0
        presisi = tp / (tp + fp) if (tp + fp) > 0 else 0
        recall = tp / (tp + fn) if (tp + fn) > 0 else 0
        
        print("\n--- METRIK EVALUASI ---")
        print(f"Akurasi Keseluruhan : {akurasi * 100:.2f}%")
        print(f"Presisi (Precision) : {presisi * 100:.2f}%")
        print(f"Sensitivitas (Recall): {recall * 100:.2f}%")
        
        print("\n--- DETAIL PER KELAS (SKLEARN) ---")
        print(classification_report(y_true, y_pred, labels=all_labels, zero_division=0))
        
    print_metrics("VISIBLE LIGHT", tp_vis, fp_vis, tn_vis, fn_vis, y_true_vis, y_pred_vis)
    print_metrics("LOW LIGHT", tp_low, fp_low, tn_low, fn_low, y_true_low, y_pred_low)
    print("="*60)

if __name__ == "__main__":
    try:
        print("Mengecek koneksi API...")
        requests.get("http://localhost:8001/docs", timeout=2)
        print("API Terhubung!\n")
        run_evaluation()
    except requests.exceptions.RequestException:
        print("\n[ERROR] API Server tidak berjalan/tidak merespon!")
        print("Pastikan api.py sedang running di terminal terpisah sebelum menjalankan test ini.")
