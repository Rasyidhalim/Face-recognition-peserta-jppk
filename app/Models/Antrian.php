<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrians';

    protected $fillable = [
        'no_registrasi',
        'no_jppk',
        'jadwal_dokter_id',
        'no_antrian',
        'tanggal_daftar',
        'status',
    ];

    public function peserta()
    {
        return $this->belongsTo(PesertaJppk::class, 'no_jppk', 'no_jppk');
    }

    public function jadwal_dokter()
    {
        return $this->belongsTo(JadwalDokter::class, 'jadwal_dokter_id');
    }
}