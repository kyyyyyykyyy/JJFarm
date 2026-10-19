<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\Keuangan;
use Carbon\Carbon;

class OwnerController extends Controller
{
    /**
     * 1. Monitoring Bobot & Laju Tumbuh (Gain)
     */
    public function monitoring()
    {
        $semuaTernak = Ternak::where('program', '!=', 'Terjual')->orderBy('updated_at', 'desc')->get();

        $performaKandang = $semuaTernak->groupBy('kandang')->map(function ($items, $kandang) {
            return [
                'kandang' => $kandang ?: 'Tanpa Kandang',
                'total_ekor' => $items->count(),
                'avg_berat' => round($items->avg('berat_kg') ?? 0, 2),
                'avg_gain' => round($items->avg('selisih_kg') ?? 0, 2),
            ];
        })->values();

        $performaRas = $semuaTernak->groupBy('ras')->map(function ($items, $ras) {
            return [
                'ras' => $ras ?: 'Campuran / Lainnya',
                'total_ekor' => $items->count(),
                'avg_gain' => round($items->avg('selisih_kg') ?? 0, 2),
                'bobot_tertinggi' => $items->max('berat_kg') ?? 0
            ];
        })->values();

        $topPerformer = $semuaTernak->sortByDesc('selisih_kg')->take(5)->values();

        return Inertia::render('Owner/MonitoringBobot', [
            'ternaks' => $semuaTernak,
            'performa_kandang' => $performaKandang,
            'performa_ras' => $performaRas,
            'top_performer' => $topPerformer
        ]);
    }

    /**
     * 2. Silsilah & Registri Pemuliaan (Breeding)
     */
    public function breeding()
    {
        $semuaTernak = Ternak::where('program', '!=', 'Terjual')->orderBy('updated_at', 'desc')->get()->map(function ($t) {
            $umur = '-';
            if ($t->tgl_lahir) {
                $diff = Carbon::parse($t->tgl_lahir)->diff(Carbon::now());
                $umur = $diff->y > 0 ? "{$diff->y} Thn {$diff->m} Bln" : "{$diff->m} Bulan";
            }

            // Penanganan aman daftar_anak (baik format array casts maupun string)
            $anakanList = [];
            if (is_array($t->daftar_anak)) {
                $anakanList = $t->daftar_anak;
            } elseif (is_string($t->daftar_anak) && strlen(trim($t->daftar_anak)) > 0) {
                $anakanList = explode(',', $t->daftar_anak);
            }

            return [
                'id' => $t->id,
                'rfid_tag' => $t->rfid_tag,
                'ras' => $t->ras ?? 'Lokal',
                'jenis_kelamin' => $t->jenis_kelamin ?? 'Betina',
                'bapak' => $t->bapak ?: 'Non-Identified',
                'mamak' => $t->mamak ?: 'Non-Identified',
                'jumlah_anak' => (int) ($t->jumlah_anak ?? 0),
                'daftar_anak' => $anakanList,
                'tgl_lahir' => $t->tgl_lahir,
                'umur' => $umur,
                'kandang' => $t->kandang ?? 'Kandang Utama',
                'status_kehamilan' => $t->status_kehamilan ?? 'N/A',
                'program' => $t->program ?? 'Produksi',
                'berat_kg' => $t->berat_kg,
            ];
        });

        // Deteksi status bunting fleksibel (menangkap 'Hamil', 'POSITIF HAMIL', dll)
        $totalBunting = $semuaTernak->filter(function ($t) {
            return str_contains(strtoupper($t['status_kehamilan'] ?? ''), 'HAMIL');
        })->count();

        $totalIndukan = $semuaTernak->where('jenis_kelamin', 'Betina')->count();
        $totalAnakan = $semuaTernak->sum('jumlah_anak');
        $pejantanAktif = $semuaTernak->pluck('bapak')->filter(fn($b) => !str_contains($b, 'Non-Identified'))->unique()->count();

        return Inertia::render('Owner/BreedingPedigree', [
            'ternaks' => $semuaTernak,
            'stats' => [
                'total_indukan' => $totalIndukan,
                'total_bunting' => $totalBunting,
                'total_anakan' => $totalAnakan,
                'pejantan_aktif' => $pejantanAktif
            ]
        ]);
    }

    /**
     * 3. Buku Kas Kandang (Cashflow Ledger)
     */
    public function keuangan()
    {
        $transaksis = Keuangan::orderBy('created_at', 'desc')->get()->map(function ($k) {
            return [
                'id' => $k->id,
                'tipe' => $k->tipe,
                'kategori' => $k->kategori ?? 'Operasional',
                'nominal' => (float) $k->nominal,
                'keterangan' => $k->keterangan ?? '-',
                'pj' => $k->pj ?? 'Sistem',
                'tanggal' => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
            ];
        });

        $totalMasuk = Keuangan::where('tipe', 'masuk')->sum('nominal');
        $totalKeluar = Keuangan::where('tipe', 'keluar')->sum('nominal');
        $saldoKas = $totalMasuk - $totalKeluar;

        $kategoriBreakdown = Keuangan::where('tipe', 'keluar')
            ->selectRaw('kategori, sum(nominal) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($totalKeluar) {
                $persentase = $totalKeluar > 0 ? round(($item->total / $totalKeluar) * 100, 1) : 0;
                return [
                    'kategori' => $item->kategori,
                    'total' => (float) $item->total,
                    'persen' => $persentase
                ];
            });

        return Inertia::render('Owner/BukuKas', [
            'transaksis' => $transaksis,
            'stats' => [
                'saldo_kas' => (float) $saldoKas,
                'total_masuk' => (float) $totalMasuk,
                'total_keluar' => (float) $totalKeluar,
                'total_transaksi' => $transaksis->count(),
            ],
            'kategori_breakdown' => $kategoriBreakdown
        ]);
    }

    public function storeKeuangan(Request $request)
    {
        $validated = $request->validate([
            'tipe' => 'required|in:masuk,keluar',
            'kategori' => 'required|string|max:100',
            'nominal' => 'required|numeric|min:1000',
            'keterangan' => 'required|string|max:255',
            'pj' => 'required|string|max:100',
        ]);

        Keuangan::create($validated);
        return redirect()->back();
    }

    public function destroyKeuangan(Keuangan $keuangan)
    {
        $keuangan->delete();
        return redirect()->back();
    }

    /**
     * 4. Proyeksi Panen & Perencanaan Komersial (Ternak Aktif & Terjual Terhubung)
     */
    public function panen()
    {
        $hargaAcuanDefault = 70000;

        // Ambil kambing aktif yang belum terjual
        $ternakAktif = Ternak::where('program', '!=', 'Terjual')
            ->orderBy('berat_kg', 'desc')
            ->get()
            ->map(function ($t) use ($hargaAcuanDefault) {
                $berat = (float) ($t->berat_kg ?? 0);
                $statusPanen = 'Tahap Pembesaran';
                if ($berat >= 32 || $t->program === 'Siap Jual') {
                    $statusPanen = 'Siap Panen';
                } elseif ($berat >= 26) {
                    $statusPanen = 'Fase Akhir (30 Hari)';
                }

                return [
                    'id' => $t->id,
                    'rfid_tag' => $t->rfid_tag,
                    'ras' => $t->ras ?? 'Lokal',
                    'jenis_kelamin' => $t->jenis_kelamin ?? 'Jantan',
                    'kandang' => $t->kandang ?? 'Kandang Utama',
                    'berat_kg' => $berat,
                    'selisih_kg' => (float) ($t->selisih_kg ?? 0),
                    'program' => $t->program ?? 'Penggemukan',
                    'status_panen' => $statusPanen,
                    'nilai_estimasi' => $berat * $hargaAcuanDefault,
                ];
            });

        // Ambil riwayat kambing yang sudah terjual
        $ternakTerjual = Ternak::where('program', 'Terjual')
            ->orderByDesc('tgl_terjual')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'rfid_tag' => $t->rfid_tag,
                    'ras' => $t->ras ?? 'Lokal',
                    'jenis_kelamin' => $t->jenis_kelamin ?? 'Jantan',
                    'kandang_asal' => $t->kandang ?? 'Kandang Utama',
                    'berat_kg' => (float) ($t->berat_kg ?? 0),
                    'harga_jual' => (float) ($t->harga_jual ?? 0),
                    'tgl_terjual' => $t->tgl_terjual ? Carbon::parse($t->tgl_terjual)->format('d M Y') : '-',
                    'pembeli' => $t->pembeli ?? 'Pembeli Umum',
                ];
            });

        $kelompokSiap = $ternakAktif->where('status_panen', 'Siap Panen');
        $kelompokMenuju = $ternakAktif->where('status_panen', 'Fase Akhir (30 Hari)');
        $totalSiap = $kelompokSiap->count();
        $totalBobotSiap = $kelompokSiap->sum('berat_kg');
        $totalTerjual = $ternakTerjual->count();
        $totalOmzetTerjual = $ternakTerjual->sum('harga_jual');

        return Inertia::render('Owner/ProyeksiPanen', [
            'ternaks' => $ternakAktif->values(),
            'ternak_terjual' => $ternakTerjual->values(),
            'stats' => [
                'total_siap' => $totalSiap,
                'total_menuju_panen' => $kelompokMenuju->count(),
                'total_bobot_siap' => round($totalBobotSiap, 1),
                'total_terjual' => $totalTerjual,
                'total_omzet_terjual' => (float) $totalOmzetTerjual,
                'harga_acuan_default' => $hargaAcuanDefault
            ]
        ]);
    }

    /**
     * Eksekusi Jual Ternak (Otomatis Masuk ke Buku Kas)
     */
    public function jualTernak(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'harga_jual' => 'required|numeric|min:1000',
            'tgl_terjual' => 'required|date',
            'pembeli' => 'nullable|string|max:150',
        ]);

        $ternak->update([
            'program' => 'Terjual',
            'harga_jual' => $validated['harga_jual'],
            'tgl_terjual' => $validated['tgl_terjual'],
            'pembeli' => $validated['pembeli'] ?: 'Pembeli Umum',
        ]);

        Keuangan::create([
            'tipe' => 'masuk',
            'kategori' => 'Penjualan Ternak',
            'nominal' => $validated['harga_jual'],
            'keterangan' => "Penjualan Ternak {$ternak->rfid_tag} ({$ternak->ras}, {$ternak->berat_kg} Kg) - " . ($validated['pembeli'] ?: 'Pembeli Umum'),
            'pj' => auth()->user()->name ?? 'Owner Utama'
        ]);

        return redirect()->back();
    }

    /**
     * Batalkan Penjualan Ternak (Otomatis Bersihkan Catatan Kas Terkait)
     */
    public function batalJualTernak(Ternak $ternak)
    {
        Keuangan::where('kategori', 'Penjualan Ternak')
            ->where('keterangan', 'like', "%{$ternak->rfid_tag}%")
            ->delete();

        $ternak->update([
            'program' => 'Siap Jual',
            'harga_jual' => null,
            'tgl_terjual' => null,
            'pembeli' => null,
        ]);

        return redirect()->back();
    }

    /**
     * 5. Halaman Registri Kambing Terjual
     */
    public function terjual()
    {
        $ternakTerjual = Ternak::where('program', 'Terjual')
            ->orderByDesc('tgl_terjual')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'rfid_tag' => $t->rfid_tag,
                    'ras' => $t->ras ?? 'Lokal',
                    'jenis_kelamin' => $t->jenis_kelamin ?? 'Jantan',
                    'kandang_asal' => $t->kandang ?? 'Kandang Utama',
                    'berat_kg' => (float) ($t->berat_kg ?? 0),
                    'harga_jual' => (float) ($t->harga_jual ?? 0),
                    'tgl_terjual' => $t->tgl_terjual ? Carbon::parse($t->tgl_terjual)->format('d M Y') : '-',
                    'pembeli' => $t->pembeli ?? 'Pembeli Umum',
                ];
            });

        $totalEkor = $ternakTerjual->count();
        $totalOmzet = $ternakTerjual->sum('harga_jual');
        $totalBobot = $ternakTerjual->sum('berat_kg');
        $avgHargaPerKg = $totalBobot > 0 ? round($totalOmzet / $totalBobot, 0) : 0;

        return Inertia::render('Owner/KambingTerjual', [
            'ternak_terjual' => $ternakTerjual->values(),
            'stats' => [
                'total_ekor' => $totalEkor,
                'total_omzet' => (float) $totalOmzet,
                'total_bobot' => round($totalBobot, 1),
                'avg_harga_per_kg' => (float) $avgHargaPerKg,
            ]
        ]);
    }
}