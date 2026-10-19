<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ternaks sudah ada dari migration sebelumnya.
        // Kita hanya membuat tabel riwayat_timbangans untuk log IoT ESP32.
        Schema::create('riwayat_timbangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ternak_id')->constrained('ternaks')->cascadeOnDelete();
            $table->string('rfid_tag', 30)->index();
            $table->string('device_id', 50);
            $table->dateTime('waktu_timbang');

            // Telemetri
            $table->decimal('berat_kg', 6, 2);
            $table->decimal('selisih_kg', 6, 2)->default(0);

            // Pemeriksaan Klinis
            $table->string('perilaku')->nullable();
            $table->string('mata_mukosa')->nullable();
            $table->string('bulu_kulit')->nullable();
            $table->string('pencernaan')->nullable();
            $table->string('pernapasan')->nullable();

            // Validasi Pakar & AI
            $table->string('prediksi_ai', 50)->nullable();
            $table->string('keputusan_pakar', 100)->nullable();
            $table->text('catatan_pakar')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_timbangans');
    }
};