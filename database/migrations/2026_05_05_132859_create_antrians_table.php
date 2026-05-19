<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::create('antrians', function (Blueprint $table) {
        $table->id();
        $table->string('no_jppk'); 
        $table->string('nama_pasien'); // Tambah ini
        $table->string('no_telp');       // Tambah ini buat kirim WhatsApp
        $table->string('poli'); 
        $table->string('no_antrian'); 
        $table->date('tanggal_daftar');
        $table->string('nama_obat')->nullable();
        $table->string('status_farmasi')->default('menunggu'); 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrians');
    }
};
