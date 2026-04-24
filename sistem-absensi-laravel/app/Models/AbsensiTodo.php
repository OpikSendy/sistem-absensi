<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiTodo extends Model
{
    protected $table = 'absensi_todo';

    protected $fillable = [
        'absensi_id', 'sumber', 'master_id', 'sub_nama',
        'manual_judul', 'manual_detail', 'jumlah', 'is_done',
    ];

    protected function casts(): array
    {
        return ['is_done' => 'boolean', 'jumlah' => 'integer'];
    }

    public function absensi()
    {
        return $this->belongsTo(Absensi::class);
    }

    public function master()
    {
        return $this->belongsTo(TugasMaster::class, 'master_id');
    }
}
