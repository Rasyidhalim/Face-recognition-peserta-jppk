import os
import requests
import glob

# ==========================================
# KONFIGURASI PENGUJIAN
# ==========================================
API_URL = "http://localhost:8001/recognize"
TEST_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), 'dataset_uji'))
DIR_TERDAFTAR = os.path.join(TEST_DIR, "terdaftar")
DIR_TIDAK_TERDAFTAR = os.path.join(TEST_DIR, "tidak_terdaftar")

def setup_folders():
    os.makedirs(DIR_TERDAFTAR, exist_ok=True)
    os.makedirs(DIR_TIDAK_TERDAFTAR, exist_ok=True)
    print("==================================================")
    print(f"Folder pengujian telah disiapkan di: \n{TEST_DIR}\n")
    print("LANGKAH SELANJUTNYA:")
    print(f"1. Masukkan foto peserta TERDAFTAR ke: {DIR_TERDAFTAR}")
    print(f"   (Buat subfolder dengan nama No JPPK-nya. Contoh: {DIR_TERDAFTAR}\\123456\\foto1.jpg)")
    print(f"2. Masukkan foto orang ASING (TIDAK TERDAFTAR) ke: {DIR_TIDAK_TERDAFTAR}")
    print(f"   (Tidak perlu subfolder, langsung saja letakkan foto misal: {DIR_TIDAK_TERDAFTAR}\\orang_asing1.jpg)")
    print("==================================================\n")

def test_image(image_path):
    try:
        with open(image_path, 'rb') as img_file:
            files = {'image': img_file}
            response = requests.post(API_URL, files=files)
            
        if response.status_code == 200:
            result = response.json()
            if result.get("status") == "success" and result.get("data"):
                # Ambil hasil deteksi dengan confidence tertinggi jika ada lebih dari 1 wajah
                best_match = max(result["data"], key=lambda x: x.get("confidence", 0))
                detected_label = best_match.get("label", "TIDAK DIKENAL")
                score = best_match.get("confidence", 0)
                return detected_label, score
            else:
                return "WAJAH_TIDAK_TERDETEKSI", 0
        else:
            return "ERROR_API", 0
    except Exception as e:
        return f"ERROR_{str(e)}", 0

def run_evaluation():
    setup_folders()
    
    # Kumpulkan path gambar (.jpg, .jpeg, .png)
    terdaftar_images = glob.glob(os.path.join(DIR_TERDAFTAR, "*", "*.[jp][pn]*[g]")) 
    tidak_terdaftar_images = glob.glob(os.path.join(DIR_TIDAK_TERDAFTAR, "*.[jp][pn]*[g]"))
    
    if not terdaftar_images and not tidak_terdaftar_images:
        print("[!] Dataset pengujian masih kosong.")
        print("Silakan isi folder dataset_uji terlebih dahulu sesuai instruksi di atas, lalu jalankan ulang script ini.")
        return

    print("Memulai Pengujian Akurasi...\n")
    
    TP = 0  # True Positive (Benar dikenali)
    FN = 0  # False Negative (Gagal dikenali)
    TN = 0  # True Negative (Asing ditolak dengan benar)
    FP = 0  # False Positive (Asing malah dikenali - BERBAHAYA)
    failed_detections = 0
    
    print("=== MENGUJI DATA TERDAFTAR (Ekspektasi: Dikenali sebagai No JPPK) ===")
    for img_path in terdaftar_images:
        expected_label = os.path.basename(os.path.dirname(img_path)) 
        filename = os.path.basename(img_path)
        
        detected_label, score = test_image(img_path)
        
        if detected_label == "WAJAH_TIDAK_TERDETEKSI":
            failed_detections += 1
            print(f"[!] {expected_label}/{filename} -> Wajah tidak tertangkap AI (Blur/Terlalu jauh/Gelap)")
            continue
            
        if detected_label == expected_label:
            TP += 1
            print(f"[BENAR] {expected_label}/{filename} -> Dikenali sebagai {detected_label} (Score: {score:.2f})")
        elif detected_label == "TIDAK DIKENAL":
            FN += 1
            print(f"[SALAH - FN] {expected_label}/{filename} -> Gagal Dikenali / Jawabannya TIDAK DIKENAL (Score: {score:.2f})")
        else:
            FN += 1 
            print(f"[SALAH - MISMATCH] {expected_label}/{filename} -> Dikenali salah sebagai {detected_label} (Score: {score:.2f})")

    print("\n=== MENGUJI DATA TIDAK TERDAFTAR / ASING (Ekspektasi: TIDAK DIKENAL) ===")
    for img_path in tidak_terdaftar_images:
        filename = os.path.basename(img_path)
        detected_label, score = test_image(img_path)
        
        if detected_label == "WAJAH_TIDAK_TERDETEKSI":
            failed_detections += 1
            print(f"[!] {filename} -> Wajah tidak tertangkap AI")
            continue
            
        if detected_label == "TIDAK DIKENAL":
            TN += 1
            print(f"[BENAR] {filename} -> Ditolak dengan benar sebagai TIDAK DIKENAL")
        else:
            FP += 1
            print(f"[SALAH - FP] {filename} -> BERBAHAYA! Orang asing malah dikenali sebagai {detected_label}! (Score: {score:.2f})")

    # Kalkulasi Metrik Akhir
    total_valid_tests = TP + TN + FP + FN
    
    if total_valid_tests == 0:
        print("\n[!] Tidak ada gambar yang berhasil dievaluasi (semua gambar gagal dideteksi wajahnya).")
        return
        
    accuracy = (TP + TN) / total_valid_tests
    precision = TP / (TP + FP) if (TP + FP) > 0 else 0
    recall = TP / (TP + FN) if (TP + FN) > 0 else 0
    
    print("\n" + "="*50)
    print("HASIL EVALUASI AKURASI FACE RECOGNITION (JPPK RS PINDAD)")
    print("="*50)
    print(f"Total Foto Diuji       : {total_valid_tests + failed_detections}")
    print(f"Gagal Deteksi (Blur/No Face) : {failed_detections}")
    print(f"Total Foto Dievaluasi  : {total_valid_tests}\n")
    
    print(f"True Positive (TP)     : {TP} (Wajah terdaftar yang BENAR dikenali)")
    print(f"True Negative (TN)     : {TN} (Orang asing yang BENAR ditolak)")
    print(f"False Positive (FP)    : {FP} (Orang asing yang SALAH dikenali - CELAH KEAMANAN)")
    print(f"False Negative (FN)    : {FN} (Wajah terdaftar yang GAGAL dikenali - SUSAH MASUK)\n")
    
    print(f"AKURASI TOTAL          : {accuracy * 100:.2f}%")
    print(f"PRESISI (Precision)    : {precision * 100:.2f}%")
    print(f"RECALL                 : {recall * 100:.2f}%")
    print("="*50)
    
    if FP > 0:
        print("KESIMPULAN: Ada orang asing yang berhasil masuk! Sistem Anda terlalu 'longgar'.")
        print("SARAN: Naikkan threshold di api.py baris 181 (misal dari 0.70 menjadi 0.75 atau 0.80).")
    elif FN > 0:
        print("KESIMPULAN: Sistem cukup aman, tapi ada peserta asli yang susah masuk (gagal dikenali).")
        print("SARAN: Jika pencahayaan saat test sudah bagus tapi tetap gagal, Anda bisa coba turunkan threshold di api.py (misal ke 0.65).")
    else:
        print("KESIMPULAN: Sistem SANGAT SEMPURNA! Tidak ada salah kenal dan tidak ada yang gagal ditolak.")

if __name__ == "__main__":
    setup_folders()
    try:
        print("Mengecek koneksi ke API Face Recognition (http://localhost:8001)...")
        requests.get("http://localhost:8001/docs", timeout=2)
        print("API Terhubung!")
        run_evaluation()
    except requests.exceptions.RequestException:
        print("\n[ERROR] API Server tidak berjalan/tidak merespon!")
        print("Pastikan api.py sedang running di terminal terpisah (menggunakan: uvicorn api:app --reload) sebelum menjalankan test ini.")
