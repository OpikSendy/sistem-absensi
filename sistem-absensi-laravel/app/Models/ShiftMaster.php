<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftMaster extends Model
{
    protected $table = 'shift_master';

    protected $fillable = [
        'nama_shift', 'jam_masuk', 'jam_pulang',
        'toleransi_menit', 'durasi_menit', 'aktif',
    ];

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'shift_id');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'shift_id');
    }
}
