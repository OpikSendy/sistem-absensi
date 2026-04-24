<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * Kolom yang bisa diisi massal.
     * Disesuaikan dengan tabel users di db_absensi_ks.
     */
    protected $fillable = [
        'username',
        'nama',
        'password',
        'role',       // 'admin' | 'user'
        'devisi',
        'aktif',      // 1 = aktif, 0 = nonaktif
        'email',
        'no_hp',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif'    => 'integer',
        ];
    }

    // ── Helpers ──────────────────────────────────────────

    /** Cek apakah user adalah admin */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Nama tampil: pakai nama jika ada, fallback ke username */
    public function getDisplayNameAttribute(): string
    {
        return $this->nama ?: $this->username;
    }

    // ── Relasi ──────────────────────────────────────────

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function shifts()
    {
        return $this->hasMany(UserShift::class);
    }

    public function jadwal()
    {
        return $this->hasMany(UserJadwal::class);
    }
}

