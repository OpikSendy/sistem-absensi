<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    // Tabel legacy tidak memiliki updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'absensi_id',
        'type',      // Contoh: 'shift_baru', 'tugas_baru', 'absen_masuk', 'absen_pulang'
        'title',
        'message',
        'is_read'
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
