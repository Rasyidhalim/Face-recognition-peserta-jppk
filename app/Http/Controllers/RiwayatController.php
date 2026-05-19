<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    /**
     * Mengambil semua riwayat pendaftaran poli
     */
    public function index()
    {
        try {
            // Langsung ambil dari tabel antrians saja Mang.
            // Tidak perlu JOIN ke peserta_jppk, biar cepet.
            $riwayat = DB::table('antrians')
                // Urutkan berdasarkan ID terbaru biar pendaftaran terakhir paling atas
                ->orderBy('id', 'desc') 
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $riwayat // Data yang dikirim adalah data asli antrians
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