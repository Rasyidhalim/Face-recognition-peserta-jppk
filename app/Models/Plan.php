<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['nama_plan', 'eselon_range'];

    public function peserta()
    {
        return $this->hasMany(PesertaJppk::class, 'plan_id');
    }
}
