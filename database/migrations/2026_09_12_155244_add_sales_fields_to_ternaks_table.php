<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ternaks', function (Blueprint $table) {
            if (!Schema::hasColumn('ternaks', 'harga_jual')) {
                $table->decimal('harga_jual', 15, 2)->nullable()->after('berat_kg');
            }
            if (!Schema::hasColumn('ternaks', 'tgl_terjual')) {
                $table->date('tgl_terjual')->nullable()->after('harga_jual');
            }
            if (!Schema::hasColumn('ternaks', 'pembeli')) {
                $table->string('pembeli')->nullable()->after('tgl_terjual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ternaks', function (Blueprint $table) {
            $table->dropColumn(['harga_jual', 'tgl_terjual', 'pembeli']);
        });
    }
};