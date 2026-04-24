<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    // Disable updated_at since legacy table doesn't have it
    const UPDATED_AT = null;

    /**
     * Kolom yang bisa diisi massal.
     * Disesuaikan dengan tabel users di db_absensi_ks.
     */
    protected $fillable = [
        'username',
        'nama',
        'password',
        'role',
        'devisi',
        'aktif',
        'no_hp',
        'foto',
        'nim',
        'jurusan',
        'asal_sekolah',
        'tanggal_lahir',
        'no_hp_orangtua'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'integer',
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

