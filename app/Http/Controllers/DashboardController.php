<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\User;
use App\Models\SecurityLog;
use App\Models\Keuangan;
use App\Models\RiwayatTimbang;

class DashboardController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;
        $semuaTernak = Ternak::orderBy('updated_at', 'desc')->get();

        // 1. DATA ANAK KANDANG (Hanya kirim ternak yang benar-benar aktif di kandang)
        if ($role === 'anak_kandang') {
            $ternakKandangAktif = Ternak::where('status_hidup', 'Aktif')
                ->where('program', '!=', 'Terjual')
                ->where('program', '!=', 'Arsip Kematian')
                ->whereNull('tgl_terjual')
                ->whereNull('tgl_kematian')
                ->orderBy('updated_at', 'desc')
                ->get();

            return Inertia::render('Dashboards/AnakKandang', [
                'ternaks' => $ternakKandangAktif
            ]);
        } 
        
        // 2. DATA OWNER (Eksekutif & Finansial - Tetap sesuai kode awal)
        if ($role === 'owner') {
            $uangMasuk = Keuangan::where('tipe', 'masuk')->sum('nominal');
            $uangKeluar = Keuangan::where('tipe', 'keluar')->sum('nominal');
            $pendapatanBersih = $uangMasuk - $uangKeluar;

            // Estimasi Valuasi Ternak: Total berat ternak hidup x Rp 65.000 / Kg
            // Hanya hitung valuasi dari ternak yang masih aktif di kandang
            $ternakHidup = $semuaTernak->filter(function ($t) {
                return $t->status_hidup === 'Aktif' && $t->program !== 'Terjual' && empty($t->tgl_terjual);
            });
            $totalBobotKg = $ternakHidup->sum('berat_kg');
            $estimasiValuasiAset = $totalBobotKg * 65000;

            return Inertia::render('Dashboards/Owner', [
                'ternaks' => $semuaTernak,
                'keuangan' => [
                    'uang_masuk'        => (float) $uangMasuk,
                    'uang_keluar'       => (float) $uangKeluar,
                    'pendapatan_kotor'  => (float) $uangMasuk,
                    'pendapatan_bersih' => (float) $pendapatanBersih,
                    'estimasi_valuasi'  => (float) $estimasiValuasiAset,
                ],
                'transaksi_terbaru' => Keuangan::latest()->take(5)->get()
            ]);
        }

        // 3. DATA SUPER ADMIN (Tetap sesuai kode awal)
        $riwayatTimbangan = RiwayatTimbang::with('ternak')->latest()->take(20)->get();

        $apiLogs = $riwayatTimbangan->map(function ($log) {
            $rfid  = $log->ternak->rfid_tag ?? $log->rfid_tag ?? 'N/A';
            $ras   = $log->ternak->ras ?? 'Domba';
            $tanda = ($log->selisih_kg >= 0 ? '+' : '');

            return [
                'id'             => $log->id,
                'waktu_masuk'    => $log->tgl_timbang ? $log->tgl_timbang->format('H:i:s d/m/Y') : $log->created_at->format('H:i:s d/m/Y'),
                'waktu'          => $log->tgl_timbang ? $log->tgl_timbang->format('H:i:s d/m/Y') : $log->created_at->format('H:i:s d/m/Y'),
                'sumber_gateway' => $log->sumber_alat ?? 'ESP32_TIMBANGAN_01',
                'sumber_alat'    => $log->sumber_alat ?? 'ESP32_TIMBANGAN_01',
                'device_id'      => $log->sumber_alat ?? 'ESP32_TIMBANGAN_01',
                'payload_data'   => "RFID: {$rfid} ({$ras}) | Bobot: {$log->berat_kg} Kg ({$tanda}{$log->selisih_kg} Kg)",
                'payload'        => "RFID: {$rfid} ({$ras}) | Bobot: {$log->berat_kg} Kg ({$tanda}{$log->selisih_kg} Kg)",
                'protokol'       => 'HTTP / JSON REST',
                'status_api'     => '201 OK / Valid',
                'status'         => 'Success',
            ];
        });

        return Inertia::render('Dashboards/SuperAdmin', [
            'ternaks'           => $semuaTernak,
            'total_users'       => User::count(),
            'total_logs'        => SecurityLog::count(),
            'total_keseluruhan' => $semuaTernak->count(),
            'total_data'        => $semuaTernak->count(),
            'data_terbaru'      => RiwayatTimbang::whereDate('created_at', today())->count(),
            'api_logs'          => $apiLogs,
            'incoming_logs'     => $apiLogs,
            'incoming_data'     => $apiLogs,
            'riwayat_timbangs'  => $apiLogs,
        ]);
    }
}