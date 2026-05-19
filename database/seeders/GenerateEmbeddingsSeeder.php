<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateEmbeddingsSeeder extends Seeder
{
    public function run()
    {
        // 1. Validasi Folder menggunakan Path Absolut Sistem
        $folderName = 'dataset_wajah';
        $fullPath = storage_path('app/' . $folderName);

        if (!is_dir($fullPath)) {
            $this->command->error("Folder TIDAK DITEMUKAN secara fisik di: " . $fullPath);
            $this->command->comment("Pastikan foldernya tidak salah eja.");
            return;
        }

        // 2. Ambil Semua File Foto (Gabungkan jpg, jpeg, JPG, JPEG agar 100% terbaca)
        $imageFiles = array_merge(
            glob($fullPath . "/*.jpg") ?: [],
            glob($fullPath . "/*.jpeg") ?: [],
            glob($fullPath . "/*.JPG") ?: [],
            glob($fullPath . "/*.JPEG") ?: []
        );

        if (empty($imageFiles)) {
            $this->command->error("Folder ketemu di {$fullPath}, tapi TIDAK ADA foto JPG/JPEG di dalamnya.");
            return;
        }

        $this->command->info("Ditemukan " . count($imageFiles) . " foto. Memulai ekstraksi ke AI Server (Port 8001)...");

        $successCount = 0;

        foreach ($imageFiles as $fullFilePath) {
            $filename = basename($fullFilePath);
            
            // Ekstrak nama file tanpa ekstensi (Misal '11150.2.jpeg' menjadi '11150.2')
            // Diubah menjadi $no_jppk agar seragam dengan nama kolom di database
            $no_jppk = pathinfo($filename, PATHINFO_FILENAME); 
            
            $this->command->comment("Memproses File: {$filename}...");

            try {
                // 3. Kirim Foto ke Python API (Pastikan Uvicorn Python sedang menyala)
                $response = Http::timeout(30)
                    ->attach('image', file_get_contents($fullFilePath), $filename)
                    ->post('http://127.0.0.1:8001/generate-embedding');

                if ($response->failed()) {
                    $this->command->error(" -> Gagal terhubung! Pastikan uvicorn Python menyala di Port 8001.");
                    continue; // Lanjut ke foto berikutnya
                }

                $result = $response->json();

                if (!isset($result['status']) || $result['status'] === 'error') {
                    $msg = $result['message'] ?? 'Unknown error dari AI';
                    $this->command->error(" -> AI Gagal mengekstrak wajah: {$msg}");
                    continue; // Lanjut ke foto berikutnya
                }

                $embeddingArray = $result['embedding'];

                // 4. Siapkan Data Relasi Dasar (Unit & Plan)
                $unit = DB::table('units')->where('nama_unit', 'PT PINDAD MEDIKA UTAMA')->first();
                $unitId = $unit ? $unit->id : DB::table('units')->insertGetId(['nama_unit' => 'PT PINDAD MEDIKA UTAMA']);

                $plan = DB::table('plans')->where('nama_plan', 'Kelas 2')->first();
                $planId = $plan ? $plan->id : DB::table('plans')->insertGetId(['nama_plan' => 'Kelas 2', 'eselon_range' => '-']);

                // 5. Simpan/Update ke Database Utama JPPK
                DB::table('peserta_jppk')->updateOrInsert(
                    ['no_jppk' => $no_jppk], 
                    [
                        'npp' => $no_jppk, 
                        'nama_peserta' => ($no_jppk == '11150.2') ? 'Rizky' : 'Peserta Baru', 
                        'jenis_kelamin' => 'L',
                        'tgl_lahir' => '1990-01-01',
                        'unit_id' => $unitId,
                        'plan_id' => $planId,
                        'face_image_path' => 'dataset_wajah/' . $filename,
                        'face_embedding' => json_encode($embeddingArray),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $this->command->info(" -> Sukses menyimpan Embedding {$no_jppk} ke MySQL!");
                $successCount++;

            } catch (\Exception $e) {
                $this->command->error(" -> Error saat memproses {$filename}: " . $e->getMessage());
            }
        }

        $this->command->info("=========================================");
        $this->command->info("Selesai! Berhasil memproses {$successCount} wajah ke Database.");
    }
}