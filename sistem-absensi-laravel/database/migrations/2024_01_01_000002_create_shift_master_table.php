<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shift_master')) return;

        Schema::create('shift_master', function (Blueprint $table) {
            $table->id();
            $table->string('nama_shift', 100);
            $table->time('jam_masuk');
            $table->time('jam_pulang')->nullable();
            $table->integer('toleransi_menit')->default(10);
            $table->integer('durasi_menit')->default(480);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_master');
    }
};
