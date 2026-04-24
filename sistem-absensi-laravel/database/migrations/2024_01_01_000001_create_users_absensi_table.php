<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk tabel users.
 * Tabel ini SUDAH ADA di db_absensi_ks.
 * Jalankan: php artisan migrate --fake (jangan drop tabel yang sudah ada)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) return; // skip jika sudah ada

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('nama', 100);
            $table->string('devisi', 100)->nullable();
            $table->string('nim', 50)->nullable();
            $table->string('jurusan', 100)->nullable();
            $table->string('asal_sekolah', 150)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('no_hp_orangtua', 20)->nullable();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->boolean('aktif')->default(true)->index();
            $table->string('foto', 255)->nullable();
            $table->rememberToken();
            $table->datetime('created_at')->useCurrent();
            $table->datetime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
