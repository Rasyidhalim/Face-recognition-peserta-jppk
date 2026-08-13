<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared(<<< 'SQL'
INSERT INTO `units` VALUES (1,'PT PINDAD MEDIKA UTAMA',NULL,NULL);
SQL
        );
    }
}
