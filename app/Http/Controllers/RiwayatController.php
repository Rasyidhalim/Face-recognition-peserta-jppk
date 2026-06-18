<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    /**
     * Mengambil semua riwayat pendaftaran poli (Sudah diperbaiki dengan JOIN)
     */
    public function index()
    {
        try {
            // Kita gabungkan tabel antrians dengan peserta_jppk berdasarkan no_jppk
            $riwayat = DB::table('antrians')
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                ->select(
                    'antrians.*', 
                    'peserta_jppk.nama_peserta' // Menarik kolom nama_peserta agar bisa tampil di riwayat
                )
                // Urutkan berdasarkan ID terbaru biar pendaftaran terakhir paling atas
                ->orderBy('antrians.id', 'desc') 
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $riwayat 
            ], 200);

        } catch (\Exception $e) {
            // Catat error di laravel.log untuk kita cek nanti
            \Log::error("Gagal ambil riwayat: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}