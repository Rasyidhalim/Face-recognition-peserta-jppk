<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaJppk extends Model
{
    protected $table = 'peserta_jppk';
    protected $primaryKey = 'no_jppk'; // Set Primary Key ke No JPPK
    public $incrementing = false;     // Matikan Auto-Increment
    protected $keyType = 'string';    // Tipe data PK adalah String

    protected $fillable = [
        'no_jppk', 'npp', 'nama_peserta', 'jenis_kelamin', 
        'tgl_lahir', 'no_telp', 'unit_id', 'plan_id', 
        'face_image_path', 'face_embedding'
    ];

    // Otomatis ubah JSON embedding menjadi Array PHP saat dipanggil
    protected $casts = [
        'face_embedding' => 'array',
        'tgl_lahir' => 'date'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}