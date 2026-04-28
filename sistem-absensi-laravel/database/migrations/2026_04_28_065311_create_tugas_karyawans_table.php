<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tugas_karyawan', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('master_id')->nullable()->index();
            $table->string('judul_tugas', 200);
            $table->text('deskripsi')->nullable();
            $table->dateTime('tenggat_waktu')->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Completed'])->default('Pending');
            $table->text('catatan_karyawan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas_karyawan');
    }
};
