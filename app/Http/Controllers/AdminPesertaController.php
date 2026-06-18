<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminPesertaController extends Controller
{
    // 1. Ambil Data Pasien (FULL ISI TERBARU)
    public function index()
    {
        try {
            // Ditambahkan orderBy agar data rapi berdasarkan nomor JPPK terbaru
            $semuaPeserta = DB::table('peserta_jppk')->orderBy('no_jppk', 'desc')->get();

            // Sesuai SQL: Yang belum registrasi adalah yang no_telp atau face_image_path-nya kosong
            $belumRegistrasiMuka = DB::table('peserta_jppk')
                ->whereNull('no_telp')
                ->orWhere('no_telp', '')
                ->orWhereNull('face_image_path')
                ->orWhere('face_image_path', '')
                ->get();

            // Kolom no_rm, status, dan nama_penanggung otomatis sudah ikut ter-load di sini karena pakai murni DB::table
            return response()->json([
                'status' => 'success', 
                'semua' => $semuaPeserta, 
                'belumLengkap' => $belumRegistrasiMuka
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. Tambah Pasien Baru (Mendukung Kolom Baru)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'no_jppk' => 'required|unique:peserta_jppk,no_jppk',
                'npp' => 'required',
                'nama_peserta' => 'required',
                'jenis_kelamin' => 'required|in:L,P',
                'tgl_lahir' => 'required|date',
                'unit_id' => 'required|numeric',
                'plan_id' => 'required|numeric',
            ]);

            DB::table('peserta_jppk')->insert([
                'no_jppk' => $request->no_jppk,
                'npp' => $request->npp,
                'nama_peserta' => $request->nama_peserta,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tgl_lahir' => $request->tgl_lahir,
                'no_telp' => $request->no_telp,
                'divisi' => $request->divisi,
                'unit_id' => $request->unit_id,
                'plan_id' => $request->plan_id,
                
                // ⚡ TAMBAHAN BARU: Agar Admin bisa input kolom ini lewat Form Tambah Pasien jika dibutuhkan
                'status' => $request->status,
                'nama_penanggung' => $request->nama_penanggung,
                'no_rm' => $request->no_rm,

                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Pasien baru berhasil didaftarkan!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal Simpan: ' . $e->getMessage()], 422);
        }
    }

    // 3. Edit Pasien Berdasarkan no_jppk (Mendukung Kolom Baru)
    public function update(Request $request, $no_jppk)
    {
        try {
            DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->update([
                'npp' => $request->npp,
                'nama_peserta' => $request->nama_peserta,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tgl_lahir' => $request->tgl_lahir,
                'no_telp' => $request->no_telp,
                'divisi' => $request->divisi,
                'unit_id' => $request->unit_id,
                'plan_id' => $request->plan_id,
                
                // ⚡ TAMBAHAN BARU: Agar Admin bisa edit kolom ini lewat Form Edit Pasien di UI Vue
                'status' => $request->status,
                'nama_penanggung' => $request->nama_penanggung,
                'no_rm' => $request->no_rm,

                'updated_at' => now()
            ]);
            return response()->json(['status' => 'success', 'message' => 'Data pasien berhasil diperbarui!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Hapus Pasien Berdasarkan no_jppk
    public function destroy($no_jppk)
    {
        try {
            DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->delete();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus dari sistem.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 5. Registrasi Wajah
    public function registrasiMukaWa(Request $request, $no_jppk)
    {
        try {
            // 1. Cek Data Pasien
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->first();
            if (!$peserta) {
                return response()->json(['status' => 'error', 'message' => 'Pasien tidak ditemukan di database.'], 404);
            }

            // 2. Pastikan ada data gambar dari kamera Vue
            if (!$request->face_image_path) {
                return response()->json(['status' => 'error', 'message' => 'Gambar wajah kosong. Silakan jepret kamera ulang.'], 400);
            }

            // 🔥 KUNCI UTAMA: Tentukan lokasi folder langsung ke storage/app/dataset_wajah (Bukan ke private!)
            $folderTujuan = storage_path('app' . DIRECTORY_SEPARATOR . 'dataset_wajah');
            
            // Jika folder luar belum ada, buat otomatis
            if (!file_exists($folderTujuan)) {
                mkdir($folderTujuan, 0775, true);
            }

            $namaFile = $no_jppk . '.jpg';
            $absolutePath = $folderTujuan . DIRECTORY_SEPARATOR . $namaFile;

            // 3. DECODE BASE64 DENGAN AMAN
            $image_parts = explode(";base64,", $request->face_image_path);
            if (count($image_parts) != 2) {
                return response()->json(['status' => 'error', 'message' => 'Format gambar Base64 dari kamera tidak valid.'], 400);
            }
            $image_base64 = base64_decode($image_parts[1]);

            // 4. Simpan paksa menggunakan fungsi murni native PHP ke folder tujuan luar
            $isSaved = file_put_contents($absolutePath, $image_base64);
            
            if ($isSaved === false) {
                return response()->json(['status' => 'error', 'message' => 'Sistem Windows menolak menulis file di lokasi: ' . $absolutePath], 500);
            }

            // Jalur relatif yang disimpan ke kolom database (Contoh hasil: dataset_wajah/11033.3.jpg)
            $pathDatabase = 'dataset_wajah/' . $namaFile;

            // 5. Kirim data gambar murni yang sukses tersimpan tadi ke FastAPI Python (Port 8001)
            $responsePython = Http::attach(
                'image', file_get_contents($absolutePath), $namaFile
            )->post('http://localhost:8001/generate-embedding');

            // 6. Evaluasi balasan dari server Python AI
            if ($responsePython->successful() && $responsePython['status'] === 'success') {
                $embeddingArray = $responsePython['embedding'];

                // Update data koordinat wajah & nomor telepon ke database MySQL
                DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->update([
                    'no_telp' => $request->no_telp,
                    'face_image_path' => $pathDatabase,
                    'face_embedding' => json_encode($embeddingArray),
                    'updated_at' => now()
                ]);

                // Instruksikan server Python untuk memuat ulang data RAM kiosk
                Http::post('http://localhost:8001/reload-data');

                return response()->json(['status' => 'success', 'message' => 'Biometrik Wajah sukses dikunci langsung ke folder luar & disinkronisasi ke Kiosk AI!']);
            } else {
                $pesanError = $responsePython['message'] ?? 'Mesin AI Python menolak gambar (Wajah tidak fokus/terdeteksi).';
                return response()->json(['status' => 'error', 'message' => $pesanError]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Fatal Error System: ' . $e->getMessage()], 500);
        }
    }
}