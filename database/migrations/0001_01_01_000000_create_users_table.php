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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->unique();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('pin', 50)->default('123456');
            $table->string('password', 255)->default('123456');
            $table->enum('role', ['karyawan', 'direktur', 'staffIT'])->default('karyawan');
            $table->string('department', 100)->default('Bidang Hubungan Langganan');
            $table->string('position', 100)->default('Seksi Pembacaan Meter');
            $table->string('phone', 30)->nullable()->default('081234567890');
            $table->string('office_id', 50)->default('hq');
            $table->string('office_name', 150)->default('Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang');
            $table->longText('avatar_url')->nullable();
            $table->string('status_pegawai', 50)->default('Karyawan Tetap');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index('nik');
            $table->index('role');
            $table->index('office_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
