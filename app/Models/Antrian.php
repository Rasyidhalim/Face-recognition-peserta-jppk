<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    protected $table = 'antrians';
    protected $fillable = ['no_jppk', 'poli', 'no_antrian', 'tanggal_daftar', 'nama_obat', 'status_farmasi'];

    // Relasi ke tabel Pasien
    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'no_jppk', 'no_jppk');
    }
}