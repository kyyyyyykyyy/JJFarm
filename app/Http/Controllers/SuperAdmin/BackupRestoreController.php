<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Ternak;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BackupRestoreController extends Controller
{
    public function index()
    {
        // Mengambil 50 log data terbaru yang masuk dari ESP32
        $dataMasuk = Ternak::orderBy('updated_at', 'desc')->take(50)->get();
        
        return Inertia::render('SuperAdmin/BackupRestore', [
            'dataLogs' => $dataMasuk
        ]);
    }

    public function export(Request $request)
    {
        $period = $request->query('period', 'all');
        $query = Ternak::query();
        $now = Carbon::now();

        // Filter berdasarkan tombol yang diklik
        if ($period === 'harian') {
            $query->whereDate('updated_at', $now->toDateString());
        } elseif ($period === 'mingguan') {
            $query->whereBetween('updated_at', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()]);
        } elseif ($period === 'bulanan') {
            $query->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year);
        } elseif ($period === 'tahunan') {
            $query->whereYear('updated_at', $now->year);
        }

        $ternaks = $query->get();
        $filename = "Backup_KandangPintar_{$period}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'RFID Tag', 'Ras', 'Kandang', 'Berat (Kg)', 'Status AI', 'Waktu Masuk Data'];

        $callback = function() use($ternaks, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns); // Header Table

            foreach ($ternaks as $t) {
                fputcsv($file, [
                    $t->id, 
                    $t->rfid_tag, 
                    $t->ras, 
                    $t->kandang, 
                    $t->berat_terakhir, 
                    $t->prediksi_ai, 
                    $t->updated_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}