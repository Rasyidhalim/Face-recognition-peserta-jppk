<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FaceRecognitionController extends Controller
{
    public function recognize(Request $request)
    {
        try {
            // 1. Terima snapshot gambar (Base64) dari kamera Kiosk Vue.js
            $base64Image = $request->input('image');
            if (!$base64Image) {
                return response()->json(['status' => 'error', 'message' => 'Kamera gagal menangkap gambar wajah.'], 400);
            }

            $imageParts = explode(";base64,", $base64Image);
            if (count($imageParts) != 2) {
                return response()->json(['status' => 'error', 'message' => 'Format gambar scanner tidak valid.'], 400);
            }
            $imageDecoded = base64_decode($imageParts[1]);

            // 2. Simpan sementara di storage local Laravel untuk dilempar ke Python
            $fileName = 'temp_scan_' . time() . '.jpg';
            Storage::put('temp/' . $fileName, $imageDecoded);
            $imagePath = storage_path('app/temp/' . $fileName);

            // 3. Tembak langsung ke Engine FastAPI Python (Port 8001) lewat jalur HTTP (Sangat Cepat & Ringan!)
            // Endpoint Python disesuaikan dengan fitur pencarian biometrik scanner Mang (misal: /search-face)
            $responsePython = Http::attach(
                'image', file_get_contents($imagePath), $fileName
            )->post('http://localhost:8001/search-face');

            // 4. Hapus foto sementara demi menghemat kapasitas Harddisk Kiosk
            Storage::delete('temp/' . $fileName);

            // 5. Evaluasi balasan dari kecerdasan buatan Python FaceNet
            if ($responsePython->successful() && isset($responsePython['status']) && $responsePython['status'] === 'success') {
                
                // Engine Python yang canggih akan langsung mengembalikan "no_jppk" yang paling cocok
                $noJppkTerdeteksi = $responsePython['no_jppk'];

                // 6. Cari data lengkap pasien ke MySQL berdasarkan NO JPPK hasil scan AI
                $pasien = DB::table('peserta_jppk AS p')
                            ->leftJoin('units AS u', 'p.unit_id', '=', 'u.id')
                            ->select('p.nama_peserta', 'p.no_jppk', 'p.divisi', 'u.nama_unit AS perusahaan', 'p.tgl_lahir')
                            ->where('p.no_jppk', $noJppkTerdeteksi)
                            ->first();

                if ($pasien) {
                    return response()->json([
                        'status' => 'success',
                        'nama' => $pasien->nama_peserta,
                        'no_jppk' => $pasien->no_jppk,
                        'divisi' => $pasien->divisi ?? '-', 
                        'unit' => $pasien->perusahaan ?? '-', 
                        'ttl' => $pasien->tgl_lahir
                    ]);
                } else {
                    return response()->json([
                        'status' => 'failed', 
                        'message' => 'Wajah dikenali sebagai JPPK ' . $noJppkTerdeteksi . ', namun rekam medis pasien tidak ditemukan di master DB.'
                    ]);
                }
            } else {
                // Jika wajah asing / tidak terdaftar di database Super DNA
                $pesanGagalAI = $responsePython['message'] ?? 'Wajah tidak dikenali. Silakan hubungi admin JPPK untuk registrasi video wajah.';
                return response()->json([
                    'status' => 'failed',
                    'message' => $pesanGagalAI
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Koneksi Kiosk ke Engine AI Terputus: ' . $e->getMessage()
            ], 500);
        }
    }
}