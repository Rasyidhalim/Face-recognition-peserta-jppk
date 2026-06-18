<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    // 1. Mengambil semua daftar poliklinik 
    // (Bisa diklik semua poli agar Abang bisa cek satu-persatu)
    public function getPoliklinik()
    {
        $polis = DB::table('polis')
                    ->select('id', 'nama_poli', 'kode_poli')
                    ->orderBy('nama_poli', 'ASC')
                    ->get();

        return response()->json($polis);
    }

    // 2. Mengambil jadwal dokter dengan Fitur Sensor Pintar untuk Debugging Kiosk
    public function getDokterByPoli($poli_id)
    {
        $dokters = DB::select("
            SELECT 
                d.id AS dokter_id,
                j.id AS jadwal_dokter_id,
                d.nama_dokter,
                IFNULL(j.hari, '-') AS hari,
                IFNULL(j.jam_mulai, '00:00:00') AS jam_mulai,
                IFNULL(j.jam_selesai, '00:00:00') AS jam_selesai,
                IFNULL(j.kuota_maksimal, 0) AS kuota_maksimal,
                IFNULL(j.kuota_terisi, 0) AS kuota_terisi,
                CASE 
                    -- KONDISI 1: Dokter ada di master, tapi di tabel 'jadwal_dokters' KOSONG TOTAL
                    WHEN j.id IS NULL THEN 'BELUM INPUT DATA JADWAL'
                    
                    -- KONDISI 2: Jadwalnya ada di DB, tapi hari ini bukan hari prakteknya (Misal jadwal besok/kemarin)
                    WHEN j.hari != ELT(DAYOFWEEK(NOW()), 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') THEN 'TUTUP (BUKAN HARI INI)'
                    
                    -- KONDISI 3: Harinya benar hari ini, tapi jam prakteknya sudah kelewat jamnya
                    WHEN CURTIME() > j.jam_selesai THEN 'TUTUP (JAM LEWAT)'
                    
                    -- KONDISI 4: Harinya benar hari ini, jamnya masuk, tapi kuota pendaftaran habis
                    WHEN j.kuota_terisi >= j.kuota_maksimal THEN 'TUTUP (KUOTA HABIS)'
                    
                    -- KONDISI 5: Lolos semua sensor, status BUKA dan tombol di Vue otomatis bisa DIKLIK!
                    ELSE 'BUKA'
                END AS status_loket
            FROM dokters d
            LEFT JOIN jadwal_dokters j ON d.id = j.dokter_id
            WHERE d.poli_id = :poli_id
            ORDER BY j.hari DESC, j.jam_mulai ASC
        ", ['poli_id' => $poli_id]);

        return response()->json($dokters);
    }
}