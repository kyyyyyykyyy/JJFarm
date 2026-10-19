<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ternaks', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_tag')->unique();
            $table->string('ras')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('bapak')->nullable();
            $table->string('mamak')->nullable();
            $table->integer('jumlah_anak')->default(0);
            $table->string('daftar_anak')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('kandang')->nullable();
            $table->string('program')->nullable();
            $table->string('status_kehamilan')->nullable();
            $table->decimal('berat_terakhir', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ternaks');
    }
};