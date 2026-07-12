<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;

    protected $table = 'polis';

    protected $fillable = [
        'nama_poli',
        'kode_poli',
    ];

    public function dokters()
    {
        return $this->hasMany(Dokter::class, 'poli_id');
    }
}
