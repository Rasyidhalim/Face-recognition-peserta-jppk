<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['nama_unit'];

    // Relasi: Satu Unit punya banyak Peserta
    public function peserta()
    {
        return $this->hasMany(PesertaJppk::class, 'unit_id');
    }
}
