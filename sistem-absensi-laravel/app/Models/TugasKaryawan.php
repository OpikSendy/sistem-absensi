<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasKaryawan extends Model
{
    protected $table = 'tugas_karyawan';

    protected $fillable = [
        'user_id',
        'master_id',
        'judul_tugas',
        'deskripsi',
        'tenggat_waktu',
        'status',
        'catatan_karyawan',
    ];

    protected $casts = [
        'tenggat_waktu' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function master()
    {
        return $this->belongsTo(TugasMaster::class, 'master_id');
    }
}
