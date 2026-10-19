<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL KANDANG DINAMIS (Bisa ditambah & disesuaikan kapan saja)
        if (!Schema::hasTable('kandangs')) {
            Schema::create('kandangs', function (Blueprint $table) {
                $table->id();
                $table->string('kode_kandang')->unique(); // Misal: A1, B1, KRT-01
                $table->string('nama_kandang'); // Misal: Kandang Kawin A1, Kandang Sapih Barat
                $table->enum('tipe_kandang', [
                    'Kawin',
                    'Bunting',
                    'Melahirkan',
                    'Menyusui',
                    'Sapih',
                    'Penggemukan',
                    'Karantina',
                    'Umum'
                ])->default('Umum');
                $table->integer('kapasitas')->default(10);
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 2. LENGKAPI TABEL TERNAKS (Siklus Kandang, Status Hidup/Mati/Jual, & Karantina)
        Schema::table('ternaks', function (Blueprint $table) {
            if (!Schema::hasColumn('ternaks', 'status_hidup')) {
                $table->enum('status_hidup', ['Aktif', 'Terjual', 'Mati'])->default('Aktif')->after('program');
            }
            if (!Schema::hasColumn('ternaks', 'tgl_kematian')) {
                $table->date('tgl_kematian')->nullable()->after('status_hidup');
            }
            if (!Schema::hasColumn('ternaks', 'penyebab_kematian')) {
                $table->string('penyebab_kematian')->nullable()->after('tgl_kematian');
            }
            if (!Schema::hasColumn('ternaks', 'tgl_masuk_kandang')) {
                $table->date('tgl_masuk_kandang')->nullable()->after('kandang');
            }
            if (!Schema::hasColumn('ternaks', 'kondisi_kesehatan')) {
                $table->enum('kondisi_kesehatan', ['Sehat', 'Sakit', 'Karantina'])->default('Sehat')->after('status_kehamilan');
            }
            if (!Schema::hasColumn('ternaks', 'persentase_sembuh')) {
                $table->integer('persentase_sembuh')->default(100)->after('kondisi_kesehatan'); // 0 - 100%
            }
            if (!Schema::hasColumn('ternaks', 'gejala_sakit')) {
                $table->text('gejala_sakit')->nullable()->after('persentase_sembuh');
            }
            if (!Schema::hasColumn('ternaks', 'penanganan_medis')) {
                $table->text('penanganan_medis')->nullable()->after('gejala_sakit');
            }
            if (!Schema::hasColumn('ternaks', 'tgl_mulai_sakit')) {
                $table->date('tgl_mulai_sakit')->nullable()->after('penanganan_medis');
            }
        });

        // 3. TABEL RIWAYAT TIMBANGAN (PENCATATAN ABADI - ZERO DATA MISS)
        if (!Schema::hasTable('riwayat_timbangs')) {
            Schema::create('riwayat_timbangs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ternak_id')->constrained('ternaks')->onDelete('cascade');
                $table->decimal('berat_kg', 8, 2);
                $table->decimal('selisih_kg', 8, 2)->default(0);
                $table->string('sumber_alat')->default('Timbangan Digital Lapangan');
                $table->timestamp('tgl_timbang')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_timbangs');
        Schema::table('ternaks', function (Blueprint $table) {
            $table->dropColumn([
                'status_hidup', 'tgl_kematian', 'penyebab_kematian',
                'tgl_masuk_kandang', 'kondisi_kesehatan', 'persentase_sembuh',
                'gejala_sakit', 'penanganan_medis', 'tgl_mulai_sakit'
            ]);
        });
        Schema::dropIfExists('kandangs');
    }
};