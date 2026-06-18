<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
    public function getAntrianPoli()
    {
        try {
            // Join yang benar untuk narik nama poli dari database
            $data = DB::table('antrians')
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                ->join('jadwal_dokters', 'antrians.jadwal_dokter_id', '=', 'jadwal_dokters.id')
                ->join('dokters', 'jadwal_dokters.dokter_id', '=', 'dokters.id')
                ->join('polis', 'dokters.poli_id', '=', 'polis.id')
                ->select(
                    'antrians.id',
                    'antrians.no_antrian',
                    'antrians.no_jppk',
                    'antrians.status',
                    'peserta_jppk.nama_peserta',
                    'peserta_jppk.no_telp',
                    'polis.nama_poli as poli' // KUNCI: Supaya Vue bisa baca item.poli
                )
                ->whereDate('antrians.tanggal_daftar', now()->format('Y-m-d')) 
                // Tampilkan pasien yang BELUM dipanggil ('menunggu') ATAU yang SEDANG dipanggil ('dipanggil')
                ->whereIn('antrians.status', ['menunggu', 'dipanggil'])
                ->orderBy('antrians.no_antrian', 'asc')
                ->get();

            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error Panggil Poli: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}