<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('absensi')) return;

        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->dateTime('waktu')->index();
            $table->date('tgl')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->text('todo')->nullable();
            $table->enum('status', ['masuk', 'pulang']);
            $table->integer('telat_menit')->default(0)->nullable();
            $table->enum('approval_status', ['Pending', 'Disetujui', 'Ditolak'])->default('Pending')->nullable();
            $table->string('foto', 255)->nullable();
            $table->string('lat', 50)->nullable();
            $table->string('lng', 50)->nullable();
            $table->string('lokasi_text', 200)->nullable();
            $table->string('ip_client', 100)->default('');
            $table->string('user_agent', 255)->default('');
            $table->date('tanggal')->index();
            $table->boolean('is_telat')->default(false);
            $table->string('devisi', 100)->nullable();
            $table->text('kendala_hari_ini')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
