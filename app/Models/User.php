<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username', // TAMBAHKAN INI (Ganti email jadi username)
        'password',
        'role',     // TAMBAHKAN INI (Supaya bisa simpan role loket/farmasi)
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // email_verified_at dihapus saja kalau tidak pakai email
            'password' => 'hashed',
        ];
    }
}