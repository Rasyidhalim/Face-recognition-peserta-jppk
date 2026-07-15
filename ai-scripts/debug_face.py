import cv2
import numpy as np
from deepface import DeepFace
from ultralytics import YOLO
import mysql.connector
import json
import traceback

img = cv2.imread(r'd:\SKRIPSI\jppk-rs-pindad\ai-scripts\dataset_uji\terdaftar\11033.3\frame_001.jpg')
if img is None:
    print("Image not found")
    exit()

model = YOLO('yolov8n.pt')
results = model(img, classes=[0], conf=0.7)

if not results or not results[0].boxes:
    print("YOLO no person detected")
    exit()

box = results[0].boxes[0]
x1, y1, x2, y2 = map(int, box.xyxy[0])
h, w, _ = img.shape
print(f"YOLO Box: {x1},{y1},{x2},{y2} (w:{x2-x1}, h:{y2-y1})")

if (x2-x1) < 150 or (y2-y1) < 150:
    print("Box too small, skipped")
    exit()

crop = img[max(0, y1-30):min(h, y2+30), max(0, x1-30):min(w, x2+30)]
print("Crop shape:", crop.shape)

try:
    res = DeepFace.represent(img_path=crop, model_name='Facenet', enforce_detection=True)
    print("DeepFace OK")
except Exception as e:
    print("DeepFace Error:", e)
    traceback.print_exc()

conn = mysql.connector.connect(host='localhost', user='root', password='', database='rs')
cur = conn.cursor(dictionary=True)
cur.execute("SELECT face_embedding FROM peserta_jppk WHERE no_jppk='11033.3'")
row = cur.fetchone()
if not row:
    print("DB no row")
else:
    db_emb = row['face_embedding']
    print('DB Type:', type(db_emb), 'Length if str:', len(db_emb) if isinstance(db_emb, str) else 'N/A')
    try:
        if isinstance(db_emb, str):
            emb = json.loads(db_emb)
            print("JSON Load OK, length:", len(emb))
    except Exception as e:
        print("JSON Load Error:", e)
