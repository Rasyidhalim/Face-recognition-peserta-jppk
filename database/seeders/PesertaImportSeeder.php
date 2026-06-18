<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PesertaImportSeeder extends Seeder
{
    public function run()
    {
        // 1. Path lokasi file Excel asli Boss (pastikan sudah ditaruh di sini)
        $filePath = storage_path('app/public/peserta_jppk.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("File 'peserta_jppk.xlsx' tidak ditemukan di folder storage/app/public/!");
            return;
        }

        $this->command->info("Sedang membaca file Excel, mohon tunggu sebentar...");

        // 2. Baca seluruh Sheet sekaligus menjadi Array
        $allSheets = Excel::toArray([], $filePath);
        
        $jumlahMasuk = 0;

        foreach ($allSheets as $sheetIndex => $rows) {
            $this->command->info("Memproses baris data pada Sheet ke-" . ($sheetIndex + 1) . "...");

            foreach ($rows as $data) {
                
                // ANTISIPASI BARIS KOSONG & HEADER
                if (empty($data) || !isset($data[2]) || (trim($data[0] ?? '') == '' && trim($data[2] ?? '') == '')) {
                    continue;
                }
                if (trim($data[0] ?? '') == 'NO.' || trim($data[1] ?? '') == 'NO. JPPK' || trim($data[3] ?? '') == 'NAMA PESERTA') {
                    continue;
                }

                // Ambil data primary key dan npp
                $noJppk = trim($data[1] ?? '');
                $npp    = trim($data[2] ?? '');
                $noUrut = trim($data[0] ?? '0');

                // Jika nomor JPPK kosong (kasus sheet Tanggungan), buat nomor bayangan unik
                if (empty($noJppk)) {
                    $noJppk = $npp . '.T' . $noUrut;
                }

                // Ambil data teks mentah dari kolom Excel untuk relasi
                $txtStrata     = trim($data[9] ?? '-');
                $txtHakRanap   = trim($data[10] ?? 'Kelas Standar');
                $txtDivisi     = trim($data[11] ?? '');
                $txtPerusahaan = trim($data[12] ?? '');

                // 3. COCOKKAN DENGAN TABEL `plans` (Kolom: nama_plan, eselon_range)
                $namaPlan   = $txtHakRanap ?: 'Kelas Standar';
                $eselonRange = $txtStrata ?: '-';

                $plan = DB::table('plans')
                    ->where('nama_plan', $namaPlan)
                    ->where('eselon_range', $eselonRange)
                    ->first();

                $planId = $plan ? $plan->id : DB::table('plans')->insertGetId([
                    'nama_plan'    => $namaPlan,
                    'eselon_range' => $eselonRange,
                    'created_at'   => now(),
                    'updated_at'   => now()
                ]);

                // 4. COCOKKAN DENGAN TABEL `units` (Kolom: nama_unit)
                // Kita prioritaskan nama Perusahaan, jika kosong pakai nama Divisi
                $namaUnit = $txtPerusahaan ?: ($txtDivisi ?: 'PT PINDAD MEDIKA UTAMA');

                $unit = DB::table('units')->where('nama_unit', $namaUnit)->first();

                $unitId = $unit ? $unit->id : DB::table('units')->insertGetId([
                    'nama_unit'  => $namaUnit,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // 5. CEK DUPLIKAT & INPUT KE `peserta_jppk`
                $cekData = DB::table('peserta_jppk')->where('no_jppk', $noJppk)->first();
                
                if (!$cekData) {
                    // Proteksi Format Tanggal khusus Excel
                    $tglRaw = $data[6] ?? '1970-01-01';
                    if (is_numeric($tglRaw)) {
                        $tglLahir = date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($tglRaw));
                    } else {
                        $tglLahir = (!empty($tglRaw) && strtotime($tglRaw)) ? date('Y-m-d', strtotime($tglRaw)) : '1970-01-01';
                    }

                    // Normalisasi Jenis Kelamin agar masuk Enum ('L','P')
                    $jkRaw = strtoupper(trim($data[8] ?? 'L'));
                    $jenisKelamin = (str_starts_with($jkRaw, 'P')) ? 'P' : 'L';

                    // Cek nomor telepon jika ada di kolom excel, atau set null
                    $noTelp = trim($data[7] ?? null);

                    DB::table('peserta_jppk')->insert([
                        'no_jppk'         => $noJppk,
                        'npp'             => $npp != '' ? $npp : $noJppk,
                        'nama_peserta'    => strtoupper(trim($data[3] ?? 'PESERTA TANPA NAMA')),
                        'jenis_kelamin'   => $jenisKelamin,
                        'tgl_lahir'       => $tglLahir,
                        'no_telp'         => $noTelp ?: null,
                        'unit_id'         => $unitId,     
                        'plan_id'         => $planId,     
                        'face_image_path' => null, // default null sesuai struktur DB asli
                        'face_embedding'  => null, // default null sesuai struktur DB asli
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $jumlahMasuk++;
                }
            }
        }

        $this->command->info("Mantap Boss! Sukses mencocokkan dan membaca langsung dari file Excel.");
        $this->command->info("Total " . $jumlahMasuk . " data peserta baru berhasil disinkronkan ke tabel database asli Boss.");
    }
}