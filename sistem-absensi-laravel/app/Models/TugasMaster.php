<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasMaster extends Model
{
    protected $table = 'tugas_master';

    protected $fillable = ['nama_tugas', 'deskripsi', 'aktif'];

    public function todos()
    {
        return $this->hasMany(AbsensiTodo::class, 'master_id');
    }
}
