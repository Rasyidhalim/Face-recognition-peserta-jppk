<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    /**
     * Mengambil semua riwayat pendaftaran poli dengan Nama Poli Asli (Menyesuaikan Database)
     */
    public function index()
    {
        try {
            $riwayat = DB::table('antrians')
                // 1. Hubungkan ke tabel peserta untuk ambil nama_peserta
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                
                // 2. Hubungkan ke tabel jadwal_dokters berdasarkan jadwal_dokter_id
                ->leftJoin('jadwal_dokters', 'antrians.jadwal_dokter_id', '=', 'jadwal_dokters.id')
                
                // 3. Hubungkan ke tabel dokters karena jadwal_dokters HANYA punya dokter_id
                ->leftJoin('dokters', 'jadwal_dokters.dokter_id', '=', 'dokters.id')
                
                // 4. Barulah hubungkan ke tabel polis melalui poli_id yang ada di tabel dokters
                ->leftJoin('polis', 'dokters.poli_id', '=', 'polis.id')
                
                ->select(
                    'antrians.*', 
                    'peserta_jppk.nama_peserta',
                    'polis.nama_poli as nama_poli' // Mengunci nama poli murni agar bisa dipanggil di Vue
                )
                // Urutkan pendaftaran terbaru di paling atas
                ->orderBy('antrians.id', 'desc') 
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $riwayat 
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Gagal ambil riwayat: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}