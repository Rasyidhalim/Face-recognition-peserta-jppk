<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminPesertaController extends Controller
{
    // 1. Ambil Data Pasien (PROSES SANITISASI ANTISIPASI DATA GHAIB)
    public function index()
    {
        try {
            // Ambil semua data peserta
            $semuaPeserta = DB::table('peserta_jppk')->orderBy('no_jppk', 'desc')->get();

            // 🔥 PENGAMAN UTAMA: Pastikan face_embedding di-decode dengan aman agar JSON tidak crash (Error 500)
            $semuaPeserta = $semuaPeserta->map(function($item) {
                if (!empty($item->face_embedding)) {
                    // Jika database mengembalikannya berupa string, decode. Jika sudah object/array (kolom JSON native), biarkan.
                    if (is_string($item->face_embedding)) {
                        $decoded = json_decode($item->face_embedding, true);
                        $item->face_embedding = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                    }
                }
                return $item;
            });

            // 🔥 PERBAIKAN LOGIKA OR: Dibungkus dalam fungsi closure agar query SQL tidak bocor
            $belumRegistrasiMuka = DB::table('peserta_jppk')
                ->where(function($query) {
                    $query->whereNull('no_telp')
                          ->orWhere('no_telp', '')
                          ->orWhereNull('face_image_path')
                          ->orWhere('face_image_path', '');
                })
                ->orderBy('no_jppk', 'desc')
                ->get();

            // Sanitisasi juga untuk data yang belum lengkap
            $belumRegistrasiMuka = $belumRegistrasiMuka->map(function($item) {
                if (!empty($item->face_embedding)) {
                    if (is_string($item->face_embedding)) {
                        $decoded = json_decode($item->face_embedding, true);
                        $item->face_embedding = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                    }
                }
                return $item;
            });

            return response()->json([
                'status' => 'success', 
                'semua' => $semuaPeserta, 
                'belumLengkap' => $belumRegistrasiMuka
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal memuat data pasien: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal memuat data server: ' . $e->getMessage()], 500);
        }
    }

    // 2. Tambah Pasien Baru
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
                'no_rm' => $request->no_rm,
                'npp' => $request->npp,
                'nama_peserta' => $request->nama_peserta,
                'status' => $request->status ?? 'Belum Aktif',
                'nama_penanggung' => $request->nama_penanggung,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tgl_lahir' => $request->tgl_lahir,
                'no_telp' => $request->no_telp, 
                'divisi' => $request->divisi,
                'unit_id' => $request->unit_id,
                'plan_id' => $request->plan_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Pasien baru berhasil didaftarkan!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal Simpan: ' . $e->getMessage()], 422);
        }
    }

    // 3. Edit Pasien 
    public function update(Request $request, $no_jppk)
    {
        try {
            DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->update([
                'no_rm' => $request->no_rm,
                'npp' => $request->npp,
                'nama_peserta' => $request->nama_peserta,
                'status' => $request->status,
                'nama_penanggung' => $request->nama_penanggung,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tgl_lahir' => $request->tgl_lahir,
                'no_telp' => $request->no_telp, 
                'divisi' => $request->divisi,
                'unit_id' => $request->unit_id,
                'plan_id' => $request->plan_id,
                'updated_at' => now()
            ]);
            return response()->json(['status' => 'success', 'message' => 'Data pasien berhasil diperbarui!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Hapus Pasien (Menghapus seluruh data pasien dari sistem)
    public function destroy($no_jppk)
    {
        try {
            // (Opsional) Jika ingin sekalian hapus file foto saat pasien dihapus total
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->first();
            if ($peserta && !empty($peserta->face_image_path)) {
                $absolutePath = storage_path('app/' . $peserta->face_image_path);
                if (file_exists($absolutePath)) {
                    unlink($absolutePath); 
                }
            }

            DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->delete();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus dari sistem.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 5. Registrasi Wajah Menggunakan Video (FUNGSI UTAMA PERBAIKAN)
    public function registrasiMukaWa(Request $request, $no_jppk)
    {
        try {
            // 🔥 PERBAIKAN 1: Tambahkan ini agar PHP Laravel tidak mati di tengah jalan
            ini_set('max_execution_time', 600); 

            // 1. Cek Data Pasien
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->first();
            if (!$peserta) {
                return response()->json(['status' => 'error', 'message' => 'Pasien tidak ditemukan di database.'], 404);
            }

            // 2. Pastikan ada file video dari Vue
            if (!$request->hasFile('video')) {
                return response()->json(['status' => 'error', 'message' => 'Rekaman video wajah kosong. Silakan rekam ulang kamera AI.'], 400);
            }

            $videoFile = $request->file('video');

            // 3. Folder Penyimpanan Sementara untuk Pengiriman
            $folderTujuan = storage_path('app' . DIRECTORY_SEPARATOR . 'dataset_wajah');
            if (!file_exists($folderTujuan)) {
                mkdir($folderTujuan, 0775, true);
            }

            $ekstensi = $videoFile->getClientOriginalExtension() ?: 'webm';
            $namaFile = $no_jppk . '.' . $ekstensi;
            $absolutePath = $folderTujuan . DIRECTORY_SEPARATOR . $namaFile;

            // Pindahkan file video sementara ke folder storage lokal
            $videoFile->move($folderTujuan, $namaFile);

            // 4. Kirim berkas video mentah dari Laravel ke FastAPI Python (Port 8001)
            // 🔥 PERBAIKAN 2: Tambahkan timeout(600) agar Laravel sabar menunggu AI bekerja
            $responsePython = Http::timeout(600)->attach(
                'video', file_get_contents($absolutePath), $namaFile
            )->post("http://localhost:8001/extract-dna/{$no_jppk}");

            // Hapus file video .webm dari folder karena tidak diperlukan lagi (agar folder tidak penuh)
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }

            // 5. Evaluasi Balasan Python & Simpan Embedding Sebenarnya
            if ($responsePython->successful()) {
                $dataPython = $responsePython->json();

                if (isset($dataPython['status']) && $dataPython['status'] === 'success') {
                    
                    $nomorTelpAman = $request->no_telp ?? $peserta->no_telp;

                    // 🧬 TANGKAP HASIL REAL EMBEDDING MENGGUNAKAN STANDARISASI DUAL KEY SAFETY
                    $embeddingMentah = $dataPython['face_embedding'] ?? $dataPython['embedding'] ?? null;
                    $pathJpgDariPython = $dataPython['face_image_path'] ?? 'dataset_wajah/' . $no_jppk . '.jpg';

                    // 🛑 PROTEKSI KETAT: Jika koordinat wajah kosong, batalkan transaksi dan beri tahu Vue
                    if (!is_array($embeddingMentah) || count($embeddingMentah) === 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kritis: Server AI mengembalikan data koordinat kosong. Mohon rekam ulang wajah dengan pencahayaan yang lebih baik.'
                        ], 400);
                    }

                    // 🔥 UTAMA: Karena kolom DB bertipe JSON asli, masukkan langsung $embeddingMentah sebagai ARRAY PHP! 
                    // Laravel Query Builder secara otomatis akan mengonversinya menjadi objek JSON MySQL yang valid.
                    DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->update([
                        'no_telp'         => $nomorTelpAman,
                        'face_image_path' => $pathJpgDariPython, 
                        'face_embedding'  => $embeddingMentah, 
                        'updated_at'      => now()
                    ]);

                    // Perintahkan Python untuk reload data cache RAM Kiosk secara realtime
                    try {
                        Http::post('http://localhost:8001/reload-data');
                    } catch (\Exception $e) {
                        Log::warning("Python reload-data gagal dihubungi, tapi database aman.");
                    }

                    return response()->json([
                        'status' => 'success', 
                        'message' => 'Biometrik Super DNA sukses diekstrak & Kolom Tipe JSON Database Terisi Sempurna!'
                    ]);
                } else {
                    $pesanError = $dataPython['message'] ?? $dataPython['detail'] ?? 'Mesin AI Python menolak rekaman video.';
                    return response()->json(['status' => 'error', 'message' => $pesanError], 400);
                }
            } else {
                $dataPython = $responsePython->json();
                $pesanError = $dataPython['detail'] ?? $dataPython['message'] ?? 'Server Python gagal merespon dengan benar (HTTP ' . $responsePython->status() . ').';
                return response()->json([
                    'status' => 'error',
                    'message' => $pesanError
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Fatal Error System: ' . $e->getMessage()], 500);
        }
    }

    // 6. Hapus Data Wajah Saja (Reset Biometrik) agar bisa rekam ulang
    // 6. Hapus Data Wajah Saja (Reset Biometrik) agar bisa rekam ulang
    public function hapusBiometrikWajah($no_jppk)
    {
        try {
            // 1. Cari data pasien berdasarkan No JPPK
            $peserta = DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->first();
            
            if (!$peserta) {
                return response()->json(['status' => 'error', 'message' => 'Pasien tidak ditemukan di database.'], 404);
            }

            // 2. 🔥 PERBAIKAN UTAMA: Sapu bersih folder dan 200 foto di dalamnya
            if (!empty($peserta->face_image_path)) {
                $absolutePath = storage_path('app/' . $peserta->face_image_path);
                $folderPath = dirname($absolutePath); // Mendapatkan path folder (dataset_wajah/NO_JPPK)
                
                if (is_dir($folderPath)) {
                    // Ambil semua file yang ada di dalam folder tersebut (*.jpg, *.webm, dll)
                    $files = glob($folderPath . DIRECTORY_SEPARATOR . '*'); 
                    
                    // Looping untuk hapus file-nya satu per satu (200 frame + video mentah)
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file); 
                        }
                    }
                    
                    // Setelah isi foldernya kosong, baru hapus foldernya
                    rmdir($folderPath); 
                }
            }

            // 3. Kosongkan kolom face_image_path & face_embedding di Database menjadi NULL
            DB::table('peserta_jppk')->where('no_jppk', $no_jppk)->update([
                'face_image_path' => null,
                'face_embedding'  => null,
                'updated_at'      => now()
            ]);

            // 4. Beritahu server Python untuk reload memori RAM AI-nya
            try {
                Http::post('http://localhost:8001/reload-data');
            } catch (\Exception $e) {
                Log::warning("Python reload-data gagal dihubungi saat hapus wajah. (Abaikan jika server python mati)");
            }

            return response()->json([
                'status' => 'success', 
                'message' => 'Seluruh dataset wajah (200 frame) dan foto utama berhasil dihapus bersih!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal menghapus folder wajah: ' . $e->getMessage()
            ], 500);
        }
    }
}