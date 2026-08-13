<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared(<<< 'SQL'
INSERT INTO `users` VALUES (1,'Petugas Farmasi','farmasi123','$2y$12$Y3mSTTGMNTgWuweqTO1RfODtZk5pQ9B9X.XS7o6uQ1jJ/nm55bvoq','farmasi',NULL,NULL,'2026-05-06 02:46:27','2026-05-06 02:46:27'),(2,'Petugas Pendaftaran','loket123','$2y$12$7WSfMdgUv62eMrgj636k6et9L8vTLQzjNt5u6hkSZ5aYhm6YYWLBa','loket',NULL,NULL,'2026-05-06 02:46:27','2026-05-06 02:46:27'),(5,'Petugas Poli','poli123','$2y$12$SdxCccdU0F2.SCG5kg0DKOpfUk5YUe2SFeq1Fre2.OFtNDw7DQZA.','poli',NULL,NULL,'2026-05-12 06:30:50','2026-05-12 06:30:50'),(6,'dr. Tester Fonnte, Sp.M','dokter','$2y$12$KIP/iPSw8sbQKT/FVFPW7eJ0t41AejYrchSN1vGgloXBA87XpQ53e','dokter',NULL,NULL,'2026-05-26 03:54:28','2026-05-26 03:54:28'),(7,'Super Admin Pindad','admin.pindad','$2y$12$isj4ImnxL3cIp9CZbFRQnu4dEW07x3.a0HwfUtIbXgBpmW0u1haJ2','admin',NULL,NULL,'2026-05-26 06:22:45','2026-05-26 06:22:45'),(8,'Super Admin Demo','superadmin','$2y$12$X3jObaGDfqbFreIt3KXEEeDoGz4GXRyg2mPgCD3JqgxgpBZhIWl5m','superadmin',NULL,NULL,'2026-06-18 08:44:10','2026-06-18 02:06:06');
SQL
        );
    }
}
