<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
public function getAntrianPoli()
{
    // Ambil data antrian hari ini berdasarkan kolom di gambar Mang
    $data = DB::table('antrians')
        ->select(
            'id',
            'no_antrian',
            'poli',
            'no_jppk',
            'nama_pasien', // Sesuai gambar
            'no_telp'      // Sesuai gambar
        )
        ->whereDate('tanggal_daftar', now()->format('Y-m-d')) // Sesuai kolom tanggal_daftar
        ->orderBy('no_antrian', 'asc')
        ->get();

    return response()->json($data);
}
}