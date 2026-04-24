<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    // Disable updated_at since legacy table doesn't have it
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'waktu', 'tgl', 'tanggal', 'status',
        'shift_id', 'telat_menit', 'is_telat', 'approval_status',
        'foto', 'lat', 'lng', 'lokasi_text',
        'keterangan', 'todo', 'kendala_hari_ini',
        'ip_client', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'waktu'       => 'datetime',
            'tanggal'     => 'date',
            'telat_menit' => 'integer',
            'is_telat'    => 'integer',
        ];
    }

    // ── Relasi ──────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(ShiftMaster::class, 'shift_id');
    }

    public function todos()
    {
        return $this->hasMany(AbsensiTodo::class);
    }

    public function details()
    {
        return $this->hasMany(AbsensiDetail::class);
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopeMasuk($query)
    {
        return $query->where('status', 'masuk');
    }

    public function scopePulang($query)
    {
        return $query->where('status', 'pulang');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'Pending');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('approval_status', 'Disetujui');
    }
}
