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
        // 1. ref_bidang
        Schema::create('ref_bidang', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('kode', 50)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // 2. ref_seksi_bidang
        Schema::create('ref_seksi_bidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidang_id')->constrained('ref_bidang')->onDelete('cascade');
            $table->string('nama', 150);
            $table->string('kode', 50)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // 3. Tabel alias `bidang` & `seksi_bidang` (untuk kompatibilitas langsung)
        Schema::create('bidang', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('kode', 50)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('seksi_bidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidang_id')->constrained('bidang')->onDelete('cascade');
            $table->string('nama', 150);
            $table->string('kode', 50)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seksi_bidang');
        Schema::dropIfExists('bidang');
        Schema::dropIfExists('ref_seksi_bidang');
        Schema::dropIfExists('ref_bidang');
    }
};

