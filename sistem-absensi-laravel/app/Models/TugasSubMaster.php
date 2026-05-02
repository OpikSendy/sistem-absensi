<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasSubMaster extends Model
{
    protected $table = 'tugas_sub_master';
    public $timestamps = false;

    protected $fillable = ['master_id', 'nama_sub', 'aktif'];

    public function master()
    {
        return $this->belongsTo(TugasMaster::class, 'master_id');
    }
}
