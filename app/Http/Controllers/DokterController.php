<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\SendsWhatsApp;

class DokterController extends Controller
{
    use SendsWhatsApp;

    /**
     * FUNGSI: Mengambil data pasien untuk Dashboard Dokter
     */
    public function getAntrianDokter()
    {
        try {
            $hariIni = now()->format('Y-m-d');

            // Ambil semua data hari ini
            $data = DB::table('antrians')
                ->join('peserta_jppk', 'antrians.no_jppk', '=', 'peserta_jppk.no_jppk')
                ->whereDate('antrians.tanggal_daftar', $hariIni)
                ->select('antrians.*', 'peserta_jppk.nama_peserta', 'peserta_jppk.no_telp', 'peserta_jppk.no_jppk')
                ->orderBy('antrians.no_antrian', 'asc')
                ->get();

            // Pisahkan data berdasarkan Status
            $antrianAktif = $data->where('status', 'dipanggil')->values();
            $riwayatSelesai = $data->where('status', 'selesai')->values();

            return response()->json([
                'aktif' => $antrianAktif,
                'riwayat' => $riwayatSelesai
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * FUNGSI: Dokter menyelesaikan proses medis
     * Ubah status 'dipanggil' -> 'selesai'
     */
    public function selesaiMedis(Request $request)
    {
        try {
            $antrianId = $request->id;

            // Ambil data beserta nama Poli
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

            // Update status menjadi 'selesai'
            DB::table('antrians')->where('id', $antrianId)->update([
                'status' => 'selesai',
                'updated_at' => now()
            ]);

            $token = env('FONNTE_TOKEN');
            
            $pesan = "*PROSES MEDIS SELESAI*\n"
                   . "*RS PINDAD*\n\n"
                   . "Halo *{$data->nama_peserta}*,\n"
                   . "Terima kasih, proses pemeriksaan medis Anda di *{$data->nama_poli}* telah dinyatakan *SELESAI*.\n\n"
                   . "🎫 No. Antrian: *{$data->no_antrian}*\n\n"
                   . "Semoga lekas sembuh dan sehat selalu. Terima kasih atas kepercayaan Anda.";

            // Tembak Fonnte API
            $this->sendWhatsApp($data->no_telp, $pesan, $token);

            return response()->json(['status' => 'success', 'message' => 'Pasien selesai diperiksa & WA terkirim!']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
