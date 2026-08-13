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
        $table->string('no_registrasi')->nullable();
        $table->string('no_jppk');
        $table->unsignedBigInteger('jadwal_dokter_id');
        $table->string('no_antrian');
        $table->date('tanggal_daftar');
        $table->enum('status', ['menunggu', 'dipanggil', 'selesai', 'batal'])->default('menunggu');
        $table->timestamps();

        $table->foreign('no_jppk')->references('no_jppk')->on('peserta_jppk')->onDelete('cascade');
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
