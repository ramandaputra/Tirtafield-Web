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
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->string('pgw_id', 50)->nullable()->index();
            $table->string('nik', 50)->nullable()->index();
            $table->string('pgw_nup', 50)->nullable();
            $table->string('pgw_nama', 150)->nullable();
            $table->date('tanggal')->index();
            $table->string('slot_type', 50)->index(); // masuk, istirahatKeluar, istirahatMasuk, pulang
            $table->string('waktu', 50);
            $table->string('lokasi', 255)->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('alamat')->nullable();
            $table->string('jarak_hq', 100)->nullable();
            $table->longText('foto_url')->nullable();
            $table->decimal('biometric_score', 5, 2)->default(99.40);
            $table->text('catatan')->nullable();
            $table->string('status', 100)->default('Tepat Waktu');
            $table->string('approval_status', 50)->default('pending')->index();
            $table->string('tipe_pengajuan', 100)->default('dalamRadius');
            $table->string('reviewed_by', 150)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['nik', 'tanggal']);
            $table->index(['pgw_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};

