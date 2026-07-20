import os
import matplotlib.pyplot as plt
import numpy as np
import argparse
from deepface import DeepFace

# Matikan log tensorflow agar bersih
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
import logging
logging.getLogger('tensorflow').setLevel(logging.FATAL)

def plot_embedding(image_path):
    print(f"Mengekstrak embedding untuk: {image_path}...")
    try:
        # Ekstrak representasi embedding menggunakan DeepFace
        # enforce_detection=False agar tidak error jika wajah terlalu dekat/kurang jelas
        objs = DeepFace.represent(img_path=image_path, model_name="Facenet", enforce_detection=False)
        
        if not objs:
            print("Gagal mengekstrak DNA wajah.")
            return
            
        embedding = objs[0]["embedding"]
        
        # Buat Plot menggunakan matplotlib
        plt.figure(figsize=(12, 4))
        plt.bar(range(len(embedding)), embedding, width=1.0)
        
        dim = len(embedding)
        plt.title(f"FaceNet Embedding ({dim} dimensi)")
        plt.xlabel(f"Dimensi (0-{dim-1})")
        plt.ylabel("Nilai")
        
        # Simpan grafik ke file
        out_filename = "grafik_embedding.png"
        plt.savefig(out_filename, bbox_inches='tight', dpi=300)
        print(f"Grafik berhasil disimpan sebagai {out_filename}")
        
        # Tampilkan grafik ke layar
        plt.show()
        
    except Exception as e:
        print(f"Gagal memproses gambar: {e}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Plot grafik embedding wajah dari gambar untuk keperluan skripsi.")
    parser.add_argument("image_path", help="Path lengkap ke file gambar (.jpg/.png)")
    args = parser.parse_args()
    
    if not os.path.exists(args.image_path):
        print(f"Error: File '{args.image_path}' tidak ditemukan.")
    else:
        plot_embedding(args.image_path)
