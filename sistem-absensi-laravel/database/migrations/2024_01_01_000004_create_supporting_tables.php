<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // user_jadwal
        if (!Schema::hasTable('user_jadwal')) {
            Schema::create('user_jadwal', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->date('tanggal')->index();
                $table->time('jam_masuk')->nullable();
                $table->time('jam_pulang')->nullable();
                $table->enum('status', ['ON', 'OFF'])->default('ON');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // user_shift
        if (!Schema::hasTable('user_shift')) {
            Schema::create('user_shift', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // tugas_master
        if (!Schema::hasTable('tugas_master')) {
            Schema::create('tugas_master', function (Blueprint $table) {
                $table->id();
                $table->string('nama_tugas', 150);
                $table->text('deskripsi')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        // absensi_todo
        if (!Schema::hasTable('absensi_todo')) {
            Schema::create('absensi_todo', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('absensi_id')->index();
                $table->enum('sumber', ['dropdown', 'manual'])->default('dropdown');
                $table->unsignedBigInteger('master_id')->nullable();
                $table->string('sub_nama', 200)->nullable();
                $table->string('manual_judul', 200)->nullable();
                $table->text('manual_detail')->nullable();
                $table->integer('jumlah')->default(1);
                $table->boolean('is_done')->default(false);
                $table->timestamps();
                $table->foreign('absensi_id')->references('id')->on('absensi')->onDelete('cascade');
            });
        }

        // absensi_detail
        if (!Schema::hasTable('absensi_detail')) {
            Schema::create('absensi_detail', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('absensi_id')->index();
                $table->string('nama_tugas', 150);
                $table->string('sub_tugas', 150)->nullable();
                $table->text('detail')->nullable();
                $table->integer('jumlah')->default(1);
                $table->string('sumber', 50)->default('manual');
                $table->timestamps();
                $table->foreign('absensi_id')->references('id')->on('absensi')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_detail');
        Schema::dropIfExists('absensi_todo');
        Schema::dropIfExists('tugas_master');
        Schema::dropIfExists('user_shift');
        Schema::dropIfExists('user_jadwal');
    }
};
