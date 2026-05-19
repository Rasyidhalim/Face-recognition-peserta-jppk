<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    // Supaya bisa input data masal
protected $fillable = [
    'no_jppk',
    'nama_pasien', // Pastikan masuk sini
    'no_wa',       // Pastikan masuk sini
    'poli',
    'no_antrian',
    'tanggal_daftar',
    'nama_obat',
    'status_farmasi'
];
}