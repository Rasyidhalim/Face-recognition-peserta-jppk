<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
{
    // 1. Isi Data Unit (diambil unik dari excel Anda)
    $units = [
        'Bangnis & Marketing', 'SDM & Pengadaan', 'Keuangan', 
        'Yanmed', 'Penunjang Medis', 'Keperawatan'
    ];
    foreach ($units as $u) {
        \App\Models\Unit::create(['nama_unit' => $u]);
    }

    // 2. Isi Data Plan (Berdasarkan kolom PLAN di excel)
    $plans = [
        ['nama_plan' => 'Kelas 1', 'eselon_range' => 'III'],
        ['nama_plan' => 'Kelas 2', 'eselon_range' => 'IVA, IVB'],
        ['nama_plan' => 'VIP', 'eselon_range' => 'I, II'],
    ];
    foreach ($plans as $p) {
        \App\Models\Plan::create($p);
    }
}
}
