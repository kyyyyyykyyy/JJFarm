<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TernakController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\SuperAdmin\BackupRestoreController; 
use App\Http\Controllers\SuperAdmin\SecurityLogController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Operator\TernakController as OperatorTernakController;
use App\Http\Controllers\Operator\ProgramController as OperatorProgramController;
use App\Http\Controllers\Operator\KandangController as OperatorKandangController;
use App\Http\Controllers\Operator\KarantinaController as OperatorKarantinaController;
use App\Http\Controllers\Operator\KasirController as OperatorKasirController;
use App\Http\Controllers\Operator\ArsipController as OperatorArsipController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/ternak/{ternak}', [TernakController::class, 'update'])->name('ternak.update');

    // WILAYAH SUPER ADMIN
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/user-management', [UserManagementController::class, 'index'])->name('superadmin.users');
        Route::post('/user-management', [UserManagementController::class, 'store'])->name('superadmin.users.store');
        Route::put('/user-management/{user}', [UserManagementController::class, 'update'])->name('superadmin.users.update');
        Route::delete('/user-management/{user}', [UserManagementController::class, 'destroy'])->name('superadmin.users.destroy');
        
        Route::get('/backup-restore', [BackupRestoreController::class, 'index'])->name('superadmin.backup');
        Route::get('/backup-restore/export', [BackupRestoreController::class, 'export'])->name('superadmin.backup.export');
        Route::get('/security-logs', [SecurityLogController::class, 'index'])->name('superadmin.logs');
    });

    // WILAYAH OWNER
    Route::middleware(['role:owner'])->prefix('owner')->name('owner.')->group(function () {
        Route::get('/monitoring-bobot', [OwnerController::class, 'monitoring'])->name('monitoring');
        Route::get('/silsilah-breeding', [OwnerController::class, 'breeding'])->name('breeding');
        Route::get('/buku-kas', [OwnerController::class, 'keuangan'])->name('keuangan');
        Route::post('/buku-kas', [OwnerController::class, 'storeKeuangan'])->name('keuangan.store');
        Route::delete('/buku-kas/{keuangan}', [OwnerController::class, 'destroyKeuangan'])->name('keuangan.destroy');
        Route::get('/proyeksi-panen', [OwnerController::class, 'panen'])->name('panen');
        Route::post('/proyeksi-panen/{ternak}/jual', [OwnerController::class, 'jualTernak'])->name('panen.jual');
        Route::post('/proyeksi-panen/{ternak}/batal-jual', [OwnerController::class, 'batalJualTernak'])->name('panen.batal');
        Route::get('/kambing-terjual', [OwnerController::class, 'terjual'])->name('terjual');
    });

    // WILAYAH ANAK KANDANG (OPERASIONAL LAPANGAN)
    Route::middleware(['role:anak_kandang'])->prefix('operator')->name('operator.')->group(function () {
        // 1. Katalog Ternak
        Route::get('/katalog-ternak', [OperatorTernakController::class, 'index'])->name('katalog');
        Route::post('/katalog-ternak', [OperatorTernakController::class, 'store'])->name('katalog.store');
        Route::put('/katalog-ternak/{ternak}', [OperatorTernakController::class, 'update'])->name('katalog.update');
        Route::post('/katalog-ternak/{ternak}/darurat-medis', [OperatorTernakController::class, 'tanggapMedis'])->name('katalog.darurat');
        Route::delete('/katalog-ternak/{ternak}', [OperatorTernakController::class, 'destroy'])->name('katalog.destroy');

        // 2. Program Pembibitan & Penggemukan
        Route::get('/program-kerja', [OperatorProgramController::class, 'index'])->name('program');
        Route::post('/program-kerja/{ternak}/usg', [OperatorProgramController::class, 'submitUsg'])->name('program.usg');
        Route::post('/program-kerja/{ternak}/timbang', [OperatorProgramController::class, 'storeTimbang'])->name('program.timbang');
        Route::post('/program-kerja/{ternak}/siap-jual', [OperatorProgramController::class, 'rilisSiapJual'])->name('program.siapJual');

        // 3. Pipeline Siklus Kandang
        Route::get('/pipeline-kandang', [OperatorKandangController::class, 'index'])->name('kandang');
        Route::post('/pipeline-kandang', [OperatorKandangController::class, 'store'])->name('kandang.store');
        Route::put('/pipeline-kandang/{kandang}', [OperatorKandangController::class, 'update'])->name('kandang.update');
        Route::delete('/pipeline-kandang/{kandang}', [OperatorKandangController::class, 'destroy'])->name('kandang.destroy');
        Route::post('/pipeline-kandang/mutasi/{ternak}', [OperatorKandangController::class, 'mutasiKandang'])->name('kandang.mutasi');

        // 4. Karantina Medis
        Route::get('/karantina-medis', [OperatorKarantinaController::class, 'index'])->name('karantina');
        Route::post('/karantina-medis/{ternak}/lapor', [OperatorKarantinaController::class, 'laporSakit'])->name('karantina.lapor');
        Route::put('/karantina-medis/{ternak}/progres', [OperatorKarantinaController::class, 'updateProgres'])->name('karantina.progres');
        Route::post('/karantina-medis/{ternak}/sembuh', [OperatorKarantinaController::class, 'verifikasiSembuh'])->name('karantina.sembuh');

        // 5. Kasir Penjualan Lapangan
        Route::get('/kasir-penjualan', [OperatorKasirController::class, 'index'])->name('kasir');
        Route::post('/kasir-penjualan/{ternak}/proses', [OperatorKasirController::class, 'prosesJual'])->name('kasir.proses');

        // 6. Gudang Data Arsip & Export Excel (RUTE BARU)
        Route::get('/gudang-arsip', [OperatorArsipController::class, 'index'])->name('arsip');
        Route::get('/gudang-arsip/export', [OperatorArsipController::class, 'export'])->name('arsip.export');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';