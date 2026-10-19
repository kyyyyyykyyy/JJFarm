<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetries', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_tag');
            $table->date('tgl_pendataan')->nullable();
            $table->decimal('berat_kg', 8, 2)->default(0);
            $table->decimal('selisih_kg', 8, 2)->default(0);
            $table->string('prediksi_ai')->nullable();
            $table->string('keputusan_pakar')->nullable();
            $table->text('catatan_pakar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetries');
    }
};