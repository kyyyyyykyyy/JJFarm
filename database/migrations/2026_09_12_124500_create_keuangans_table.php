<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keuangans', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->string('kategori')->nullable(); // Pakan, Medis, Penjualan Ternak, dll
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->string('pj')->nullable(); // Penanggung Jawab
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keuangans');
    }
};