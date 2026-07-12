import pandas as pd
import mysql.connector
from datetime import datetime
import re

# DB Config
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'rs'
}

def clean_time(time_str):
    if not isinstance(time_str, str) or time_str.strip() in ['-', '', 'nan']:
        return None, None
    time_str = re.sub(r'\(.*?\)', '', time_str)
    time_str = re.sub(r'[a-zA-Z\s]', '', time_str)
    
    parts = time_str.split('-')
    if len(parts) == 2:
        try:
            start = parts[0].replace('.', ':').strip()
            end = parts[1].replace('.', ':').strip()
            if len(start.split(':')) == 2: start += ':00'
            if len(end.split(':')) == 2: end += ':00'
            return start, end
        except Exception:
            return None, None
    return None, None

print("[INFO] Memulai sinkronisasi data dari Excel...")
conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor()

print("[INFO] Mengosongkan tabel lama...")
cursor.execute("SET FOREIGN_KEY_CHECKS=0;")
cursor.execute("TRUNCATE TABLE antrians;")
cursor.execute("TRUNCATE TABLE jadwal_dokters;")
cursor.execute("TRUNCATE TABLE dokters;")
cursor.execute("TRUNCATE TABLE polis;")
cursor.execute("SET FOREIGN_KEY_CHECKS=1;")
conn.commit()

poli_map = {}
dokter_map = {}
kode_poli_set = set()

valid_days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']

def normalize_day(d):
    for valid in valid_days:
        if valid.upper() in d.upper():
            return valid
    return None

count_poli = 0
count_dokter = 0
count_jadwal = 0

sheets = ['JADWAL DOKTER (SPESIALIS)', 'JADWAL DOKTER (LAIN-LAIN)']
file_path = 'database/data/JADWAL & KUOTA DOKTER INSTALASI RAWAT JALAN RS PINDAD BANDUNG.xlsx'

for sheet in sheets:
    print(f"[INFO] Membaca sheet: {sheet}")
    df = pd.read_excel(file_path, sheet_name=sheet, header=1)
    
    if sheet == 'JADWAL DOKTER (SPESIALIS)':
        poli_col = 1; dokter_col = 2; hari_col = 3; jam_col = 4; kuota_col = 11
    else:
        poli_col = 1; dokter_col = 2; hari_col = 3; jam_col = 4; kuota_col = 6
        
    df.iloc[:, poli_col] = df.iloc[:, poli_col].ffill()
    df.iloc[:, dokter_col] = df.iloc[:, dokter_col].ffill()
    
    for index, row in df.iterrows():
        poli_raw = str(row.iloc[poli_col]).strip()
        dokter_raw_multi = str(row.iloc[dokter_col]).strip()
        hari_raw = str(row.iloc[hari_col]).strip()
        jam_raw = str(row.iloc[jam_col]).strip()
        kuota_raw = str(row.iloc[kuota_col]).strip()
        
        if poli_raw == 'nan' or dokter_raw_multi == 'nan':
            continue
            
        hari_valid = normalize_day(hari_raw)
        
        nama_poli = poli_raw.upper()
        if not nama_poli.startswith('POLI'):
            nama_poli = 'POLIKLINIK ' + nama_poli
            
        # POLI INSERT
        if nama_poli not in poli_map:
            base_kode = re.sub(r'[^A-Z]', '', nama_poli.replace('POLIKLINIK', '').replace('POLI EKSEKUTIF', ''))
            if len(base_kode) == 0: base_kode = 'UMM'
            kode_poli = base_kode[:3]
            
            original_kode = kode_poli
            suffix = 1
            while kode_poli in kode_poli_set:
                kode_poli = f"{original_kode[:2]}{suffix}"
                suffix += 1
                
            kode_poli_set.add(kode_poli)
            
            cursor.execute(
                "INSERT INTO polis (nama_poli, kode_poli, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
                (nama_poli, kode_poli)
            )
            poli_map[nama_poli] = cursor.lastrowid
            count_poli += 1

        poli_id = poli_map[nama_poli]

        # MULTIPLE DOCTORS (LAIN-LAIN)
        for single_dokter in dokter_raw_multi.split('\n'):
            dokter_raw = single_dokter.strip()
            if not dokter_raw: continue

            # DOKTER INSERT
            if dokter_raw not in dokter_map:
                cursor.execute(
                    "INSERT INTO dokters (nama_dokter, poli_id, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
                    (dokter_raw, poli_id)
                )
                dokter_map[dokter_raw] = cursor.lastrowid
                count_dokter += 1

            dokter_id = dokter_map[dokter_raw]

            # JADWAL INSERT
            if hari_valid:
                start_time, end_time = clean_time(jam_raw)
                if start_time and end_time:
                    kuota = 30
                    if kuota_raw.isdigit():
                        kuota = int(kuota_raw)
                        
                    cursor.execute(
                        """INSERT INTO jadwal_dokters 
                        (dokter_id, hari, jam_mulai, jam_selesai, kuota_maksimal, kuota_terisi, created_at, updated_at) 
                        VALUES (%s, %s, %s, %s, %s, 0, NOW(), NOW())""",
                        (dokter_id, hari_valid, start_time, end_time, kuota)
                    )
                    count_jadwal += 1

conn.commit()
cursor.close()
conn.close()

print(f"[SUCCESS] Sinkronisasi Selesai!")
print(f"  - Total Poli: {count_poli}")
print(f"  - Total Dokter: {count_dokter}")
print(f"  - Total Jadwal Praktek: {count_jadwal}")
