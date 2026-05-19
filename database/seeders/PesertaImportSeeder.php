<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PesertaJppk;
use App\Models\Unit;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PesertaImportSeeder extends Seeder
{
    public function run()
    {
        $file = database_path('data/JPPK PMU.csv');
        
        if (!file_exists($file)) {
            $this->command->error("File tidak ditemukan di: $file");
            return;
        }

        // Matikan query log agar cepat
        DB::connection()->disableQueryLog();

        $this->command->info("Membaca file JPPK PMU.csv (Robust Mode)...");

        // 1. Baca seluruh isi file sebagai satu string panjang
        $rawContent = file_get_contents($file);

        if (!$rawContent) {
            $this->command->error("Gagal membaca isi file.");
            return;
        }

        // 2. HAPUS BOM (Byte Order Mark) UTF-8 tersembunyi dari Excel
        $bom = pack('H*', 'EFBBBF');
        $rawContent = preg_replace("/^$bom/", '', $rawContent);

        // 3. Normalisasi Newlines gaya Windows (\r\n) menjadi gaya Linux (\n)
        $normalizedContent = str_replace(["\r\n", "\r"], "\n", $rawContent);

        // 4. Pecah string panjang menjadi array baris-baris teks
        $lines = explode("\n", $normalizedContent);

        $rowCount = 0;
        $invalidLines = 0;

        $this->command->info("Memulai proses parsing manual...");

        // Karena sudah unggah file, kita hardcode delimiter KOMA (,) agar pasti.
        $delimiter = ",";

        foreach ($lines as $index => $line) {
            $currentLineNumber = $index + 1;

            // A. Lewati 5 baris pertama (Judul Laporan, Header Kolom, Baris Kosong)
            // Data utama mulai di baris ke-6 (Index 5)
            if ($currentLineNumber < 6) continue;

            // Skip jika baris benar-benar kosong
            if (empty(trim($line))) continue;

            // B. Gunakan str_getcsv untuk memecah teks baris menjadi array kolom
            // Fungsi ini jauh lebih aman daripada explode(',') terhadap tanda kutip Excel
            $data = str_getcsv($line, $delimiter);

            // C. VALIDASI KRUSIAL: Cek jumlah kolom (Metdata CSV Anda ada 10 kolom)
            if (count($data) < 10) {
                // Catat ke log untuk debug jika ada baris yang formatnya aneh
                Log::warning("Baris $currentLineNumber dilewati. Jumlah kolom tidak lengkap: " . count($data));
                $invalidLines++;
                continue; // LEWATI BARIS INI
            }

            // D. Ambil data mentah (Mapping pas berdasarkan file CSV Anda)
            $namaPesertaVal = trim($data[1]);
            $noJppkVal      = trim($data[2]); // Primary Key (Contoh: 11150.1)
            $jkVal          = strtoupper(trim($data[3])); // KELAMIN
            $tglLahirVal    = trim($data[4]); // TGL. LAHIR (Sudah YYYY-MM-DD)
            $eselonVal      = trim($data[6]); // ESELON (Contoh: 'A', 'C', 'VIA')
            $planNomorVal   = trim($data[7]); // PLAN (Contoh: 1 atau 2)
            $nppVal         = trim($data[8]); // NPP
            $namaUnitVal    = trim($data[9]); // UNIT

            // E. Validasi: No JPPK tidak boleh kosong dan bukan teks header berulang
            if (empty($noJppkVal) || $noJppkVal == 'NO. JPPK') continue;

            try {
                DB::beginTransaction();

                // F. Olah Master Data (Cari atau Buat baru)
                $unit = Unit::firstOrCreate(['nama_unit' => $namaUnitVal]);
                
                $planName = 'Kelas ' . ($planNomorVal ?: '2');
                $plan = Plan::firstOrCreate(
                    ['nama_plan' => $planName],
                    ['eselon_range' => $eselonVal ?: '-']
                );

                // G. Masukkan / Update data Peserta
                PesertaJppk::updateOrCreate(
                    ['no_jppk' => $noJppkVal],
                    [
                        'npp'           => $nppVal,
                        'nama_peserta'  => $namaPesertaVal,
                        'jenis_kelamin' => in_array($jkVal, ['L', 'P']) ? $jkVal : 'L',
                        // Tanggal sudah standard MySQL, langsung save
                        'tgl_lahir'     => ($tglLahirVal && $tglLahirVal != '-') ? $tglLahirVal : '1900-01-01',
                        'unit_id'       => $unit->id,
                        'plan_id'       => $plan->id,
                    ]
                );

                DB::commit();
                $rowCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                // Catat error fatal ke storage/logs/laravel.log
                Log::error("Gagal mengimport Baris CSV $currentLineNumber (JPPK $noJppkVal): " . $e->getMessage());
                $this->command->error("Error fatal pada Baris $currentLineNumber (Cek laravel.log)");
            }
        }

        $this->command->info("---------------------------------");
        $this->command->info("SELESAI!");
        $this->command->info("Berhasil diimport/sync: $rowCount data pasien ke database.");
        if ($invalidLines > 0) {
            $this->command->comment("Baris rusak/terpotong yang dilewati: $invalidLines baris (Cek laravel.log untuk detail).");
        }
    }
}