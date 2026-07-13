<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\SendsWhatsApp;

class PoliController extends Controller
{
    use SendsWhatsApp;

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

    /**
     * FUNGSI: Untuk Memanggil Pasien ke Poli dari Loket/Dokter
     * Status berubah dari 'menunggu' -> 'dipanggil'
     */
    public function panggilPoli(Request $request)
    {
        try {
            $antrianId = $request->id;
            
            // Ambil data pasien berserta nama polinya dengan JOIN
            $data = DB::table('antrians')
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                ->join('jadwal_dokters', 'antrians.jadwal_dokter_id', '=', 'jadwal_dokters.id')
                ->join('dokters', 'jadwal_dokters.dokter_id', '=', 'dokters.id')
                ->join('polis', 'dokters.poli_id', '=', 'polis.id')
                ->where('antrians.id', $antrianId)
                ->select(
                    'antrians.no_antrian', 
                    'peserta_jppk.nama_peserta', 
                    'peserta_jppk.no_telp',
                    'polis.nama_poli'
                )
                ->first();

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
            }

            // Update status menjadi 'dipanggil'
            DB::table('antrians')->where('id', $antrianId)->update([
                'status' => 'dipanggil',
                'updated_at' => now()
            ]);

            $token = env('FONNTE_TOKEN');
            $pesan = "*PANGGILAN POLIKLINIK RS PINDAD*\n\n"
                   . "Halo *{$data->nama_peserta}*,\n"
                   . "📢 Nomor Antrian Anda *{$data->no_antrian}* sudah dipanggil.\n"
                   . "Mohon segera menuju ke *{$data->nama_poli}*.\n\n"
                   . "Terima kasih atas kerjasamanya.";

            // Tembak Fonnte API
            $this->sendWhatsApp($data->no_telp, $pesan, $token);

            return response()->json(['status' => 'success', 'message' => 'Notifikasi Panggilan Terkirim']);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}