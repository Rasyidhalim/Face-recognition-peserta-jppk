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
    Schema::create('plans', function (Blueprint $table) {
        $table->id();
        $table->string('nama_plan');    // Contoh: Kelas 1, Kelas 2, VIP
        $table->string('eselon_range'); // Contoh: IVA, IVB, III
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
