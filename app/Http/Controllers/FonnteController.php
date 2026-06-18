<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FonnteController extends Controller
{
    /**
     * FUNGSI 1: Untuk Pendaftaran di Kiosk (Main.vue)
     * - Auto-generate No Registrasi (Hanya disimpan di DB internal)
     * - Auto-generate No Antrian berurutan per Poli
     * - Kirim WhatsApp: Berisi data diri + Poli + Dokter (TANPA No Registrasi)
     */
    public function daftarAntrian(Request $request)
    {
        try {
            $noJppk = $request->no_jppk;
            $jadwalDokterId = $request->jadwal_dokter_id; 

            // 1. Ambil data pasien
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $noJppk)->first();
            if (!$peserta) {
                return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
            }

            // 2. Ambil data Jadwal Dokter
            $jadwal = DB::table('jadwal_dokters')
                ->join('dokters', 'jadwal_dokters.dokter_id', '=', 'dokters.id')
                ->join('polis', 'dokters.poli_id', '=', 'polis.id')
                ->where('jadwal_dokters.id', $jadwalDokterId)
                ->select(
                    'jadwal_dokters.id', 
                    'dokters.nama_dokter', 
                    'polis.nama_poli'
                )
                ->first();

            if (!$jadwal) {
                return response()->json(['message' => 'Jadwal dokter tidak ditemukan'], 404);
            }
            
            // 🚨 TRICK TESTING: Hardcode nama Poli dan Dokter untuk bypass testing
            $poli = $jadwal->nama_poli; 
            $dokter = $jadwal->nama_dokter;

            $no_telp = $peserta->no_telp;
            $hariIni = now()->format('Y-m-d');
            $hariIniCompact = now()->format('Ymd');

            // 🔥 PERBAIKAN ERROR STATUS: Deteksi aman kolom status kepesertaan
            $statusPeserta = isset($peserta->status) ? $peserta->status : 'Peserta';

            // 3. GENERATE NOMOR REGISTRASI (Masuk DB internal, TIDAK dikirim ke WA)
            $noRegistrasi = 'REG-' . $hariIniCompact . '-' . rand(1000, 9999);

            // 4. LOGIKA GENERATE NOMOR ANTRIAN BERURUT PER POLI
            $namaBarsihPoli = trim(str_ireplace('Poli', '', $poli)); 
            $kodePoli = strtoupper(substr($namaBarsihPoli, 0, 1)); // Mengambil huruf depan poli (Contoh: M)

            // Cari urutan antrian terakhir hari ini untuk jadwal_dokter_id yang sama
            $antrianTerakhir = DB::table('antrians')
                ->where('jadwal_dokter_id', $jadwalDokterId)
                ->where('tanggal_daftar', $hariIni)
                ->orderBy('id', 'desc')
                ->first();

            if ($antrianTerakhir) {
                $angkaTerakhir = (int) substr($antrianTerakhir->no_antrian, 2);
                $urutanBerikutnya = $angkaTerakhir + 1;
            } else {
                $urutanBerikutnya = 1;
            }

            // Format menjadi 3 digit (Contoh: M-001)
            $noAntrian = $kodePoli . '-' . str_pad($urutanBerikutnya, 3, '0', STR_PAD_LEFT);

            // 5. SIMPAN KE TABEL ANTRIANS (Sesuai skema database asli Mang Dicky)
            DB::table('antrians')->insert([
                'no_registrasi'     => $noRegistrasi, 
                'no_jppk'           => $noJppk,
                'jadwal_dokter_id'  => $jadwalDokterId,
                'no_antrian'        => $noAntrian,
                'tanggal_daftar'    => $hariIni,
                'status'            => 'menunggu', 
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // 6. KIRIM WA KONFIRMASI (Tanpa No Registrasi sesuai request)
            $token = env('FONNTE_TOKEN');
            $pesan = "*RS PINDAD*\n\n"
                   . "Halo *{$peserta->nama_peserta}* (NPP: {$peserta->npp}),\n"
                   . "Pendaftaran Anda Berhasil Diproses!\n\n"
                   . "Status Kepesertaan: *JPPK PINDAD*\n"
                   . "Hubungan Keluarga: *{$statusPeserta}*\n" 
                   . "🩺 *Poli Tujuan:* {$poli}\n"
                   . "👨‍⚕️ *Dokter:* {$dokter}\n\n"
                   . "🎫 *NOMOR ANTRIAN:* *{$noAntrian}*\n\n"
                   . "Silakan tunggu panggilan Anda di ruang tunggu. Terima kasih.";

            $this->sendWhatsApp($no_telp, $pesan, $token);

            return response()->json([
                'status' => 'success',
                'no_registrasi' => $noRegistrasi, 
                'antrian' => $noAntrian,
                'poli'    => $poli,
                'dokter'  => $dokter,
                'nama'    => $peserta->nama_peserta
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FUNGSI 2: Untuk Memanggil Pasien ke Poli dari Loket/Dokter
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

    /**
     * FUNGSI 3: Mengambil data pasien untuk Dashboard Dokter
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
     * FUNGSI 4: Dokter menyelesaikan proses medis
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
    /**
     * HELPER: Fungsi kirim WA
     */
    private function sendWhatsApp($target, $pesan, $token)
    {
        return Http::withHeaders(['Authorization' => $token])
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $pesan,
                'countryCode' => '62',
            ]);
    }
}
