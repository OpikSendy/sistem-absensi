<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJadwal extends Model
{
    protected $table = 'user_jadwal';

    protected $fillable = [
        'user_id', 'shift_id', 'tanggal',
        'jam_masuk', 'jam_pulang', 'status', // ON | OFF
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(ShiftMaster::class, 'shift_id');
    }
}
