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
        $table->string('no_jppk')->primary(); 
        $table->string('no_rm', 50)->nullable();
        $table->string('npp')->index();
        $table->string('status');
        $table->string('nama_peserta');
        $table->string('nama_penanggung')->nullable();
        $table->enum('jenis_kelamin', ['L', 'P']);
        $table->date('tgl_lahir');
        $table->string('no_telp')->nullable();
        $table->string('divisi')->nullable();
        
        $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
        $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');

        $table->string('face_image_path')->nullable();
        $table->json('face_embedding')->nullable();

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
