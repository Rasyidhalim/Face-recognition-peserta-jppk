<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call(DokterSeeder::class);
        $this->call(JadwalDokterSeeder::class);
        $this->call(PesertaJppkSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(PoliSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(UserSeeder::class);

        Schema::enableForeignKeyConstraints();
    }
}
