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
    Schema::create('peserta_jppk', function (Blueprint $table) {
        // Primary Key manual menggunakan No JPPK
        $table->string('no_jppk')->primary(); 
        
        // Data Dasar Pasien
        $table->string('npp')->index(); // NPP bisa sama untuk satu keluarga
        $table->string('nama_peserta');
        $table->enum('jenis_kelamin', ['L', 'P']);
        $table->date('tgl_lahir');
        $table->string('no_telp')->nullable();
        
        // Hubungan ke Master Data (Foreign Key)
        $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
        $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');

        // Kolom Khusus AI Face Recognition
        $table->string('face_image_path')->nullable(); // Path foto di storage
        $table->json('face_embedding')->nullable();   // Hasil koordinat wajah dari Python

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_jppk');
    }
};
