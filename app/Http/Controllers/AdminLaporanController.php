<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLaporanController extends Controller
{
    public function getLaporan(Request $request)
    {
        try {
            // Melakukan JOIN berantai sesuai dengan skema rs (3).sql
            $query = DB::table('antrians')
                // 1. Join ke tabel peserta untuk mengambil data pasien (Nama & No RM)
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                
                // 2. Join ke tabel jadwal_dokters lewat jadwal_dokter_id
                ->join('jadwal_dokters', 'antrians.jadwal_dokter_id', '=', 'jadwal_dokters.id')
                
                // 3. Join ke tabel dokters lewat dokter_id dari tabel jadwal
                ->join('dokters', 'jadwal_dokters.dokter_id', '=', 'dokters.id')
                
                // 4. Join ke tabel polis lewat poli_id dari tabel dokter
                ->join('polis', 'dokters.poli_id', '=', 'polis.id')
                
                // Memilih kolom yang akan ditampilkan dengan menggunakan Alias (as) agar sesuai dengan Vue
                ->select(
                    'antrians.id',
                    'antrians.no_registrasi',
                    'antrians.no_antrian',
                    'antrians.tanggal_daftar',
                    'antrians.status as status_antrian', // enum: menunggu, dipanggil, selesai, batal
                    'peserta_jppk.no_rm',
                    'peserta_jppk.nama_peserta as nama_pasien',
                    'polis.nama_poli as nama_poli',
                    'dokters.nama_dokter as nama_dokter'
                )
                // Urutkan berdasarkan tanggal daftar terbaru, lalu nomor antrean terkecil
                ->orderBy('antrians.tanggal_daftar', 'desc')
                ->orderBy('antrians.no_antrian', 'asc');

            // =========================================================
            // PROSES FILTER PENCARIAN BERDASARKAN INPUT ADMIN DARI VUE
            // =========================================================

            // Filter 1: Berdasarkan Tanggal Daftar
            if ($request->has('tanggal') && $request->tanggal != '') {
                $query->whereDate('antrians.tanggal_daftar', $request->tanggal);
            }

            // Filter 2: Berdasarkan Pencarian Teks Nama Poliklinik
            if ($request->has('poli') && $request->poli != '') {
                $query->where('polis.nama_poli', 'LIKE', '%' . $request->poli . '%');
            }

            // Filter 3: Berdasarkan Pencarian Teks Nama Dokter
            if ($request->has('dokter') && $request->dokter != '') {
                $query->where('dokters.nama_dokter', 'LIKE', '%' . $request->dokter . '%');
            }

            // Eksekusi Query
            $laporan = $query->get();

            // Kirim ke Vue
            return response()->json([
                'status' => 'success',
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            // Tangkap Error jika ada salah ketik kolom
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat laporan: ' . $e->getMessage()
            ], 500);
        }
    }
}