<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $table = 'dokters';

    protected $fillable = [
        'nip',
        'nama_dokter',
        'poli_id',
        'no_telp',
    ];

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }

    public function jadwal_dokters()
    {
        return $this->hasMany(JadwalDokter::class, 'dokter_id');
    }
}
