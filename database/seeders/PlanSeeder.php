<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared(<<< 'SQL'
INSERT INTO `plans` VALUES (1,'Kelas 2','-',NULL,NULL),(2,'I NS','VIP','2026-05-26 08:39:53','2026-05-26 08:39:53'),(3,'III','1','2026-05-26 08:39:53','2026-05-26 08:39:53'),(4,'V','2','2026-05-26 08:39:53','2026-05-26 08:39:53');
SQL
        );
    }
}
