import os
import mysql.connector

# Konfigurasi Database (Sesuaikan jika berbeda dengan di api.py)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'rs'
}

# Path ke dataset
LARAVEL_DATASET_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'dataset_wajah'))

def sync_database():
    print("=" * 50)
    print("[INFO] MEMULAI SINKRONISASI DATASET WAJAH & DATABASE")
    print("=" * 50)
    
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        # Ambil semua peserta yang tercatat memiliki data wajah (face_embedding tidak null)
        query = "SELECT no_jppk, nama_peserta FROM peserta_jppk WHERE face_embedding IS NOT NULL"
        cursor.execute(query)
        peserta_terdaftar = cursor.fetchall()
        
        cleaned_count = 0
        
        for peserta in peserta_terdaftar:
            no_jppk = peserta['no_jppk']
            nama = peserta['nama_peserta']
            
            # Cek apakah folder wajahnya ada di local storage
            folder_path = os.path.join(LARAVEL_DATASET_PATH, str(no_jppk))
            
            if not os.path.exists(folder_path):
                print(f"[!] Folder tidak ditemukan untuk: {no_jppk} - {nama}. Menghapus data wajah dari database...")
                
                # Update database: hapus face_embedding dan face_image_path
                update_query = """
                    UPDATE peserta_jppk 
                    SET face_embedding = NULL, face_image_path = NULL 
                    WHERE no_jppk = %s
                """
                cursor.execute(update_query, (no_jppk,))
                conn.commit()
                
                cleaned_count += 1
                
        print("-" * 50)
        print(f"[SUCCESS] Sinkronisasi selesai. Total {cleaned_count} data dibersihkan karena foldernya hilang.")
        print("=" * 50)

    except Exception as e:
        print(f"[ERROR] Terjadi kesalahan: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    sync_database()
