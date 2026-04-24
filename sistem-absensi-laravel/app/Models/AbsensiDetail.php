<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiDetail extends Model
{
    protected $table = 'absensi_detail';

    protected $fillable = [
        'absensi_id', 'nama_tugas', 'sub_tugas',
        'detail', 'jumlah', 'sumber',
    ];

    protected function casts(): array
    {
        return ['jumlah' => 'integer'];
    }

    public function absensi()
    {
        return $this->belongsTo(Absensi::class);
    }
}
