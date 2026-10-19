<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ternaks', function (Blueprint $table) {
            if (!Schema::hasColumn('ternaks', 'ras')) $table->string('ras')->nullable()->after('rfid_tag');
            if (!Schema::hasColumn('ternaks', 'jenis_kelamin')) $table->string('jenis_kelamin')->nullable()->after('ras');
            if (!Schema::hasColumn('ternaks', 'bapak')) $table->string('bapak')->nullable()->after('jenis_kelamin');
            if (!Schema::hasColumn('ternaks', 'mamak')) $table->string('mamak')->nullable()->after('bapak');
            if (!Schema::hasColumn('ternaks', 'jumlah_anak')) $table->integer('jumlah_anak')->default(0)->after('mamak');
            if (!Schema::hasColumn('ternaks', 'daftar_anak')) $table->text('daftar_anak')->nullable()->after('jumlah_anak');
            if (!Schema::hasColumn('ternaks', 'tgl_lahir')) $table->date('tgl_lahir')->nullable()->after('daftar_anak');
            if (!Schema::hasColumn('ternaks', 'tgl_pendataan')) $table->date('tgl_pendataan')->nullable()->after('tgl_lahir');
            if (!Schema::hasColumn('ternaks', 'berat_kg')) $table->decimal('berat_kg', 8, 2)->default(0)->after('kandang');
            if (!Schema::hasColumn('ternaks', 'selisih_kg')) $table->decimal('selisih_kg', 8, 2)->default(0)->after('berat_kg');
            if (!Schema::hasColumn('ternaks', 'program')) $table->string('program')->nullable()->after('selisih_kg');
            if (!Schema::hasColumn('ternaks', 'status_kehamilan')) $table->string('status_kehamilan')->nullable()->after('program');
            if (!Schema::hasColumn('ternaks', 'prediksi_ai')) $table->string('prediksi_ai')->nullable()->after('status_kehamilan');
            if (!Schema::hasColumn('ternaks', 'keputusan_pakar')) $table->string('keputusan_pakar')->nullable()->after('prediksi_ai');
            if (!Schema::hasColumn('ternaks', 'catatan_pakar')) $table->text('catatan_pakar')->nullable()->after('keputusan_pakar');
        });
    }

    public function down(): void
    {
        Schema::table('ternaks', function (Blueprint $table) {
            $table->dropColumn([
                'ras', 'jenis_kelamin', 'bapak', 'mamak', 'jumlah_anak', 
                'daftar_anak', 'tgl_lahir', 'tgl_pendataan', 'berat_kg', 
                'selisih_kg', 'program', 'status_kehamilan', 'prediksi_ai', 
                'keputusan_pakar', 'catatan_pakar'
            ]);
        });
    }
};