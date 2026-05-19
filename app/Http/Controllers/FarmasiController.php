<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FarmasiController extends Controller
{
    public function index()
    {
        $hariIni = now()->format('Y-m-d');

        // Menampilkan antrian yang HANYA hari ini saja
        // Supaya P-001 kemarin tidak muncul lagi di layar petugas hari ini
        $data = DB::table('antrians')
            ->where('tanggal_daftar', $hariIni)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($data);
    }

    public function riwayat()
    {
        // Riwayat farmasi biasanya untuk melihat yang sudah diberi obat
        // Ini tidak perlu filter hari ini agar bisa rekap data kemarin-kemarin
        $data = DB::table('antrians')
            ->whereNotNull('nama_obat')
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($data);
    }
}