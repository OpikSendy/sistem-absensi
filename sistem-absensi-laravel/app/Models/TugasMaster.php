<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasMaster extends Model
{
    protected $table = 'tugas_master';
    public $timestamps = false;

    protected $fillable = ['nama_tugas', 'kategori', 'aktif'];

    public function todos()
    {
        return $this->hasMany(AbsensiTodo::class, 'master_id');
    }
}
