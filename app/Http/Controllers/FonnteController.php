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
     * Sekarang sudah pakai nomor berurut (P-001, P-002) dan reset tiap hari
     */
    public function daftarAntrian(Request $request)
    {
        try {
            $noJppk = $request->no_jppk;
            $poli = $request->poli; 

            // 1. Ambil data pasien
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $noJppk)->first();

            if (!$peserta) {
                return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
            }

            $no_telp = $peserta->no_telp;
            $hariIni = now()->format('Y-m-d');

            // 2. LOGIKA GENERATE NOMOR ANTRIAN BERURUT
            // 2. LOGIKA GENERATE NOMOR ANTRIAN BERURUT (SUDAH DIPERBAIKI)
// Kita buang kata "Poli " nya dulu Mang, baru ambil huruf depannya
$namaBersihPoli = trim(str_ireplace('Poli', '', $poli)); // Misal: "Poli Mata" jadi "Mata"
$kodePoli = strtoupper(substr($namaBersihPoli, 0, 1)); // Mengambil huruf "M", "U", atau "G"

// Cari antrian terakhir di poli yang sama pada hari ini
$antrianTerakhir = DB::table('antrians')
    ->where('poli', $poli)
    ->where('tanggal_daftar', $hariIni)
    ->orderBy('id', 'desc')
    ->first();

if ($antrianTerakhir) {
    // Mengambil angka urutan setelah tanda strip (posisi ke-2)
    $angkaTerakhir = (int) substr($antrianTerakhir->no_antrian, 2);
    $urutanBerikutnya = $angkaTerakhir + 1;
} else {
    $urutanBerikutnya = 1;
}

// Format menjadi 3 digit (Contoh hasil akhir: M-001, U-001, G-001)
$noAntrian = $kodePoli . '-' . str_pad($urutanBerikutnya, 3, '0', STR_PAD_LEFT);

            // 3. SIMPAN KE TABEL ANTRIANS
            DB::table('antrians')->insert([
                'no_jppk'        => $noJppk,
                'nama_pasien'    => $peserta->nama_peserta, 
                'no_telp'        => $no_telp,
                'poli'           => $poli,
                'no_antrian'     => $noAntrian,
                'tanggal_daftar' => $hariIni,
                'status_farmasi' => 'menunggu',
                'status_panggilan' => 'belum', // Menandai pasien baru belum dipanggil
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 4. Kirim WA Konfirmasi
            $token = env('FONNTE_TOKEN');
            $pesan = "*RS PINDAD MEDIKA UTAMA*\n\n"
                   . "Halo *{$peserta->nama_peserta}*,\n"
                   . "Pendaftaran Berhasil!\n\n"
                   . "Tujuan: {$poli}\n"
                   . "No Antrian: *{$noAntrian}*\n\n"
                   . "Silakan tunggu panggilan di ruang tunggu. Terima kasih.";

            $this->sendWhatsApp($no_telp, $pesan, $token);

            return response()->json([
                'status' => 'success',
                'antrian' => $noAntrian,
                'poli'    => $poli,
                'nama' => $peserta->nama_peserta,
                'wa_status' => 'Terkirim ke ' . $no_telp
            ]);

        } catch (\Exception $e) {
            Log::error("Kiosk Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FUNGSI 2: Untuk Notifikasi Obat Siap
     */
    public function notifObatFarmasi(Request $request)
    {
        try {
            $antrianId = $request->id;
            $namaObat = $request->nama_obat;

            $data = DB::table('antrians')->where('id', $antrianId)->first();

            if (!$data) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            DB::table('antrians')->where('id', $antrianId)->update([
                'nama_obat' => $namaObat,
                'status_farmasi' => 'siap',
                'updated_at' => now(),
            ]);

            $token = env('FONNTE_TOKEN');
            $pesan = "*NOTIFIKASI FARMASI RS PINDAD*\n\n"
                   . "Halo *{$data->nama_pasien}*,\n"
                   . "Obat Anda: *{$namaObat}* sudah siap.\n"
                   . "No Antrian: *{$data->no_antrian}*.\n";

            $this->sendWhatsApp($data->no_telp, $pesan, $token); 

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * FUNGSI 3: Untuk Memanggil Pasien ke Poli dari Loket
     */
    public function panggilPoli(Request $request)
    {
        try {
            $antrianId = $request->id;
            $data = DB::table('antrians')->where('id', $antrianId)->first();

            if (!$data) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            $token = env('FONNTE_TOKEN');
            $pesan = "*PANGGILAN POLIKLINIK RS PINDAD*\n\n"
                   . "Halo *{$data->nama_pasien}*,\n"
                   . "📢 Nomor Antrian Anda *{$data->no_antrian}* sudah dipanggil.\n"
                   . "Mohon segera menuju ke *{$data->poli}*.\n\n"
                   . "Terima kasih atas kerjasamanya.";

            $this->sendWhatsApp($data->no_telp, $pesan, $token);

            // UPDATE STATUS DISINI
            DB::table('antrians')->where('id', $antrianId)->update([
                'status_panggilan' => 'sudah',
                'updated_at' => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Notifikasi Panggilan Terkirim']);
            
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