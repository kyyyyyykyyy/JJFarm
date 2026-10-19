<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\RiwayatTimbang;
use App\Models\Keuangan;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function index()
    {
        // Hanya tampilkan kambing aktif yang benar-benar siap jual dan tidak sedang sakit/karantina
        $kambingSiapJual = Ternak::where('status_hidup', 'Aktif')
            ->where(function ($q) {
                $q->where('program', 'Siap Jual')
                  ->orWhere(function ($sub) {
                      $sub->where('berat_kg', '>=', 30)
                          ->where('kondisi_kesehatan', 'not like', '%Sakit%')
                          ->where('kondisi_kesehatan', 'not like', '%Karantina%')
                          ->where('kondisi_kesehatan', 'not like', '%Isolasi%');
                  });
            })
            ->orderByDesc('berat_kg')
            ->get()
            ->map(function ($t) {
                $estimasiHarga = (float) $t->berat_kg * 65000;
                return [
                    'id'             => $t->id,
                    'rfid_tag'       => $t->rfid_tag,
                    'ras'            => $t->ras ?? 'Lokal',
                    'jenis_kelamin'  => $t->jenis_kelamin ?? 'Jantan',
                    'berat_kg'       => (float) ($t->berat_kg ?? 0),
                    'kandang'        => $t->kandang ?? 'Blok A',
                    'program'        => $t->program,
                    'estimasi_harga' => $estimasiHarga,
                    'catatan_pakar'  => $t->catatan_pakar ?? 'Siap potong / kurban',
                ];
            });

        $hariIni = now()->toDateString();
        $transaksiHariIni = Ternak::where('status_hidup', 'Terjual')
            ->whereDate('tgl_terjual', $hariIni)
            ->orderByDesc('updated_at')
            ->get(['id', 'rfid_tag', 'ras', 'berat_kg', 'harga_jual', 'pembeli', 'tgl_terjual']);

        $totalOmzetHariIni = $transaksiHariIni->sum('harga_jual');

        return Inertia::render('Operator/KasirPenjualan', [
            'kambing_ready'      => $kambingSiapJual,
            'transaksi_hari_ini' => $transaksiHariIni,
            'stats'              => [
                'total_ready'      => $kambingSiapJual->count(),
                'terjual_hari_ini' => $transaksiHariIni->count(),
                'omzet_hari_ini'   => (float) $totalOmzetHariIni,
                'avg_harga'        => $transaksiHariIni->count() > 0 ? round($totalOmzetHariIni / $transaksiHariIni->count(), 0) : 0,
            ]
        ]);
    }

    public function prosesJual(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'harga_jual'        => 'required|numeric|min:10000',
            'berat_final'       => 'required|numeric|min:1',
            'pembeli'           => 'required|string|max:150',
            'metode_pembayaran' => 'required|string|in:Tunai (Cash),Transfer Bank,QRIS',
            'tujuan_transaksi'  => 'required|string|max:100',
            'catatan'           => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated, $ternak) {
            $beratLama = (float) $ternak->berat_kg;
            $beratBaru = (float) $validated['berat_final'];
            $selisih   = round($beratBaru - $beratLama, 2);

            // 1. Catat log timbang kasir jika ada selisih timbangan
            if (abs($beratBaru - $beratLama) > 0.001) {
                RiwayatTimbang::create([
                    'ternak_id'   => $ternak->id,
                    'berat_kg'    => $beratBaru,
                    'selisih_kg'  => $selisih,
                    'sumber_alat' => 'Timbangan Kasir Lapangan',
                    'tgl_timbang' => now(),
                ]);
            }

            // 2. Update status ternak menjadi Terjual
            $keteranganPembeli = "{$validated['pembeli']} [{$validated['tujuan_transaksi']}] ({$validated['metode_pembayaran']})";
            $ternak->update([
                'berat_kg'      => $beratBaru,
                'selisih_kg'    => $selisih,
                'status_hidup'  => 'Terjual',
                'program'       => 'Terjual',
                'harga_jual'    => $validated['harga_jual'],
                'pembeli'       => $keteranganPembeli,
                'tgl_terjual'   => now()->toDateString(),
                'catatan_pakar' => "Kasir Lapangan: " . ($validated['catatan'] ?: 'Lunas di tempat.'),
            ]);

            // 3. Masukkan otomatis ke Buku Kas Keuangan Owner
            Keuangan::create([
                'tipe'       => 'masuk',
                'kategori'   => 'Penjualan Ternak',
                'nominal'    => $validated['harga_jual'],
                'keterangan' => "Penjualan Ternak RFID {$ternak->rfid_tag} ({$ternak->ras}, {$beratBaru} Kg) kepada {$validated['pembeli']}",
                'pj'         => auth()->user()->name ?? 'Petugas Kasir Lapangan'
            ]);
        });

        return redirect()->back();
    }
}