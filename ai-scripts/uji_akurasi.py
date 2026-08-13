import os
import csv
import cv2
import numpy as np
import requests
import warnings
import glob
from sklearn.metrics import classification_report, confusion_matrix, ConfusionMatrixDisplay
import matplotlib.pyplot as plt

warnings.filterwarnings('ignore')

# ==========================================
# KONFIGURASI PENGUJIAN
# ==========================================
API_URL = "http://localhost:8001/recognize"
TEST_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), 'dataset_uji'))
DIR_TERDAFTAR = os.path.join(TEST_DIR, "terdaftar")
DIR_TIDAK_TERDAFTAR = os.path.join(TEST_DIR, "tidak_terdaftar")

def test_image_api(image_bytes):
    """Mengirim gambar ke API dan mengembalikan label dan confidence-nya."""
    try:
        files = {'image': ('test.jpg', image_bytes, 'image/jpeg')}
        response = requests.post(API_URL, files=files)
        
        if response.status_code == 200:
            result = response.json()
            if result.get("status") == "success" and result.get("data"):
                best_match = max(result["data"], key=lambda x: x.get("confidence", 0))
                detected_label = best_match.get("label", "TIDAK DIKENAL")
                confidence = best_match.get("confidence", 0)
                return detected_label, confidence
            else:
                return "TIDAK DIKENAL", 0.0
        else:
            return "ERROR_API", 0.0
    except Exception as e:
        return "ERROR_API", 0.0

def run_test(images_terdaftar, images_tidak_terdaftar):
    y_true = []
    y_pred = []
    detailed_results = []
    
    tp = 0 # True Positive: Terdaftar dan dikenali benar
    fn = 0 # False Negative: Terdaftar tapi gagal dikenali/salah orang
    fp = 0 # False Positive: Orang asing dikenali sebagai terdaftar
    tn = 0 # True Negative: Orang asing ditolak (Tidak dikenal)
    
    # UJI TERDAFTAR
    for i, img_path in enumerate(images_terdaftar):
        expected_label = os.path.basename(os.path.dirname(img_path))
        print(f"      [Uji {i+1}/{len(images_terdaftar)}] Mengetes wajah: {expected_label}...", end=" ", flush=True)
        frame_to_test = cv2.imread(img_path)
        if frame_to_test is None:
            print("GAGAL (Gambar Rusak)")
            continue
            
        _, buffer = cv2.imencode('.jpg', frame_to_test)
        detected_label, conf = test_image_api(buffer.tobytes())
        
        y_true.append(expected_label)
        if detected_label == "TIDAK DIKENAL" or detected_label == "ERROR_API":
            print(f"Hasil: {detected_label} -> [FALSE NEGATIVE]")
            y_pred.append("Tidak dikenal")
            fn += 1
            status = "FALSE NEGATIVE (Gagal Kenal)"
        elif detected_label != expected_label:
            print(f"Hasil: {detected_label} (Akurasi: {conf*100:.1f}%) -> [FALSE NEGATIVE (wajah mirip {detected_label})]")
            y_pred.append(detected_label)
            fn += 1
            status = f"FALSE NEGATIVE (Mirip {detected_label})"
        else:
            print(f"Hasil: {detected_label} (Akurasi: {conf*100:.1f}%) -> [TRUE POSITIVE]")
            y_pred.append(detected_label)
            tp += 1
            status = "TRUE POSITIVE"
            
        detailed_results.append({
            "File": os.path.basename(img_path),
            "Identitas Asli": expected_label,
            "Tebakan AI": detected_label,
            "Akurasi (%)": round(conf * 100, 2),
            "Status": status
        })

    # BAGIAN ORANG ASING (TIDAK TERDAFTAR) - Tetap ada kerangkanya jika nanti diisi lagi
    for i, img_path in enumerate(images_tidak_terdaftar):
        print(f"      [Uji Asing {i+1}/{len(images_tidak_terdaftar)}] Mengetes orang asing...", end=" ", flush=True)
        frame_to_test = cv2.imread(img_path)
        if frame_to_test is None:
            print("GAGAL (Gambar Rusak)")
            continue
            
        _, buffer = cv2.imencode('.jpg', frame_to_test)
        detected_label, conf = test_image_api(buffer.tobytes())
        
        y_true.append("Tidak dikenal")
        if detected_label == "TIDAK DIKENAL" or detected_label == "ERROR_API":
            print(f"Hasil: {detected_label} -> [TRUE NEGATIVE]")
            y_pred.append("Tidak dikenal")
            tn += 1
            status = "TRUE NEGATIVE"
        else:
            print(f"Hasil: {detected_label} (Akurasi: {conf*100:.1f}%) -> [FALSE POSITIVE (Kebobolan!)]")
            y_pred.append(detected_label)
            fp += 1
            status = "FALSE POSITIVE"
            
        detailed_results.append({
            "File": os.path.basename(img_path),
            "Identitas Asli": "Orang Asing",
            "Tebakan AI": detected_label,
            "Akurasi (%)": round(conf * 100, 2),
            "Status": status
        })
            
    return y_true, y_pred, tp, fp, tn, fn, detailed_results

def run_evaluation():
    print("Memulai Pengujian Akurasi (Dataset Uji Khusus)...")
    
    terdaftar_images = []
    dataset_wajah_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'dataset_wajah'))
    if os.path.exists(dataset_wajah_dir):
        for person_folder in os.listdir(dataset_wajah_dir):
            person_path = os.path.join(dataset_wajah_dir, person_folder)
            if os.path.isdir(person_path):
                frames = sorted(glob.glob(os.path.join(person_path, "*.jpg")))
                # Abaikan frame_001.jpg karena dipakai di database
                frames = [f for f in frames if "frame_001" not in f]
                # Ambil hingga 5 foto per orang secara merata
                if frames:
                    step = max(1, len(frames) // 5)
                    selected_frames = frames[::step][:5]
                    terdaftar_images.extend(selected_frames)
                    
    tidak_terdaftar_images = []
    
    total_images = len(terdaftar_images) + len(tidak_terdaftar_images)
    if total_images == 0:
        print(f"[!] Dataset pengujian kosong di {dataset_wajah_dir}")
        return
        
    print(f"Total gambar yang akan diuji: {total_images} (5 foto/orang, TANPA orang asing)")
    print("  -> Pengujian sedang berjalan...\n")
    y_true, y_pred, tp, fp, tn, fn, detailed_results = run_test(terdaftar_images, tidak_terdaftar_images)
    
    # Export ke Excel menggunakan pandas
    excel_filename = "hasil_pengujian.xlsx"
    try:
        import pandas as pd
        df = pd.DataFrame(detailed_results)
        
        # Mapping status ke singkatan (tp/fn/fp/tn) huruf kecil
        def get_kategori(status):
            if 'TRUE POSITIVE' in status: return 'tp'
            elif 'FALSE NEGATIVE' in status: return 'fn'
            elif 'TRUE NEGATIVE' in status: return 'tn'
            elif 'FALSE POSITIVE' in status: return 'fp'
            return 'Lainnya'
            
        df['Kategori'] = df['Status'].apply(get_kategori)
        
        # Buat urutan pengujian (1, 2, 3, 4, 5) per orang
        df['Pengujian Ke'] = df.groupby('Identitas Asli').cumcount() + 1
        
        final_rows = []
        for identitas in df['Identitas Asli'].unique():
            df_person = df[df['Identitas Asli'] == identitas]
            
            # Baris pertama tiap blok: [Identitas Asli, 1, 2, 3, 4, 5]
            final_rows.append([identitas, 1, 2, 3, 4, 5])
            
            # Crosstab hitungan per orang ini
            crosstab = pd.crosstab(df_person['Kategori'], df_person['Pengujian Ke'])
            
            # Tambahkan baris untuk tiap metrik
            for kat in ['tp', 'fn', 'fp', 'tn']:
                row = [kat]
                for i in range(1, 6):
                    val = crosstab.loc[kat, i] if (kat in crosstab.index and i in crosstab.columns) else 0
                    row.append(val)
                final_rows.append(row)
                
            # Baris kosong pemisah antar orang
            final_rows.append(['', '', '', '', '', ''])
            
        df_final = pd.DataFrame(final_rows)
        
        # Ekspor ke Excel tanpa header kolom default
        df_final.to_excel(excel_filename, index=False, header=False)
        print(f"\n[V] Laporan Excel (Tabel Blok per Orang) berhasil diekspor ke: {excel_filename}")
    except ImportError:
        print("\n[X] Modul 'pandas' atau 'openpyxl' belum terinstall. Install dengan: pip install pandas openpyxl")
    except Exception as e:
        print(f"\n[X] Gagal mengekspor Excel: {e}")
    
    # Ambil semua label unik untuk urutan confusion matrix sklearn (Detail per kelas)
    all_labels = sorted(list(set(y_true + y_pred)))
    if "Tidak dikenal" not in all_labels:
        all_labels.append("Tidak dikenal")
        
    print("\n" + "="*60)
    print(" HASIL PENGUJIAN AKURASI KARYAWAN TERDAFTAR ")
    print("="*60)
    print("--- HASIL EVALUASI IDENTIFIKASI (CLOSED-SET) ---")
    print(f"True Positive (TP)  : {tp} (Terdaftar & Dikenali Benar)")
    print(f"False Negative (FN) : {fn} (Terdaftar Tapi Gagal Dikenali / Salah Orang)")
    
    total = tp + fp + tn + fn
    akurasi = (tp + tn) / total if total > 0 else 0
    presisi = tp / (tp + fp) if (tp + fp) > 0 else 0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0
    
    print("\n--- METRIK EVALUASI ---")
    print(f"Akurasi Keseluruhan : {akurasi * 100:.2f}%")
    print(f"Presisi (Precision) : {presisi * 100:.2f}%")
    print(f"Sensitivitas (Recall): {recall * 100:.2f}%")
    
    print("\n--- DETAIL KESALAHAN PER ORANG (SKLEARN) ---")
    print(classification_report(y_true, y_pred, labels=all_labels, zero_division=0))
    print("="*60)
    
    # ---------------------------------------------------------
    # GENERATE GAMBAR CONFUSION MATRIX
    # ---------------------------------------------------------
    print("\nMembuat gambar Confusion Matrix...")
    try:
        cm = confusion_matrix(y_true, y_pred, labels=all_labels)
        disp = ConfusionMatrixDisplay(confusion_matrix=cm, display_labels=all_labels)
        
        # Atur ukuran gambar agar label tidak bertumpuk
        fig, ax = plt.subplots(figsize=(10, 8))
        disp.plot(cmap=plt.cm.Blues, ax=ax, xticks_rotation=45)
        
        plt.title('Confusion Matrix - Identifikasi Wajah')
        plt.tight_layout()
        
        out_file = "confusion_matrix.png"
        plt.savefig(out_file, dpi=300)
        print(f"[V] Gambar Confusion Matrix berhasil disimpan sebagai: {out_file}")
    except Exception as e:
        print(f"[X] Gagal membuat gambar Confusion Matrix: {e}")

if __name__ == "__main__":
    try:
        print("Mengecek koneksi API...")
        requests.get("http://localhost:8001/docs", timeout=2)
        print("API Terhubung!\n")
        run_evaluation()
    except requests.exceptions.RequestException:
        print("\n[ERROR] API Server tidak berjalan/tidak merespon!")
        print("Pastikan api.py sedang running di terminal terpisah sebelum menjalankan test ini.")
