<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArsipController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'All');

        $query = Ternak::with(['riwayatTimbangs' => function ($q) {
            $q->orderByDesc('tgl_timbang')->take(10);
        }])->orderByDesc('updated_at');

        // Query filter SQL yang aman terhadap tipe data status_hidup
        if ($statusFilter === 'Terjual') {
            $query->where(function ($q) {
                $q->where('status_hidup', 'Terjual')
                  ->orWhere('program', 'Terjual')
                  ->orWhereNotNull('tgl_terjual')
                  ->orWhere('harga_jual', '>', 0);
            });
        } elseif ($statusFilter === 'Mati') {
            $query->where(function ($q) {
                $q->where('status_hidup', 'Mati')
                  ->orWhere('program', 'Arsip Kematian')
                  ->orWhereNotNull('tgl_kematian')
                  ->orWhereNotNull('penyebab_kematian');
            });
        } elseif ($statusFilter === 'Aktif') {
            $query->where('program', '!=', 'Terjual')
                  ->where('program', '!=', 'Arsip Kematian')
                  ->whereNull('tgl_terjual')
                  ->whereNull('tgl_kematian')
                  ->where(function ($q) {
                      $q->where('status_hidup', 'Aktif')
                        ->orWhereNull('status_hidup')
                        ->orWhere('status_hidup', 1)
                        ->orWhere('status_hidup', true);
                  });
        }

        $semuaTernak = Ternak::all();

        // Identifikasi ID masing-masing kategori secara akurat
        $idTerjual = $semuaTernak->filter(function ($t) {
            return $t->program === 'Terjual' || !empty($t->tgl_terjual) || (float)$t->harga_jual > 0 || (string)$t->status_hidup === 'Terjual';
        })->pluck('id')->toArray();

        $idMati = $semuaTernak->filter(function ($t) {
            return (!empty($t->tgl_kematian) || !empty($t->penyebab_kematian) || $t->program === 'Arsip Kematian' || (string)$t->status_hidup === 'Mati') && !in_array($t->id, $idTerjual);
        })->pluck('id')->toArray();

        $arsipTernak = $query->get()->map(function ($t) use ($idTerjual, $idMati) {
            // Tentukan status hidup yang pasti berupa string
            $statusFixed = 'Aktif';
            if (in_array($t->id, $idTerjual)) {
                $statusFixed = 'Terjual';
            } elseif (in_array($t->id, $idMati)) {
                $statusFixed = 'Mati';
            }

            $umur = '-';
            if ($t->tgl_lahir) {
                $diff = Carbon::parse($t->tgl_lahir)->diff(Carbon::now());
                $umur = $diff->y > 0 ? "{$diff->y} Thn {$diff->m} Bln" : "{$diff->m} Bulan";
            }

            return [
                'id'                => $t->id,
                'rfid_tag'          => $t->rfid_tag,
                'ras'               => $t->ras ?? 'Lokal',
                'jenis_kelamin'     => $t->jenis_kelamin ?? 'Betina',
                'bapak'             => $t->bapak ?: 'Non-Identified',
                'mamak'             => $t->mamak ?: 'Non-Identified',
                'jumlah_anak'       => (int) ($t->jumlah_anak ?? 0),
                'tgl_lahir'         => $t->tgl_lahir ?: '-',
                'umur'              => $umur,
                'berat_kg'          => (float) ($t->berat_kg ?? 0),
                'selisih_kg'        => (float) ($t->selisih_kg ?? 0),
                'kandang'           => $t->kandang ?? '-',
                'program'           => $t->program ?? '-',
                'status_hidup'      => $statusFixed, // Dijamin string 'Aktif', 'Terjual', atau 'Mati'
                'status_kehamilan'  => $t->status_kehamilan ?? 'Non-Bunting',
                'kondisi_kesehatan' => $t->kondisi_kesehatan ?? 'Sehat',
                'gejala_sakit'      => $t->gejala_sakit ?: 'Tidak ada catatan sakit',
                'penanganan_medis'  => $t->penanganan_medis ?: 'Pemeriksaan rutin sehat',
                'keputusan_pakar'   => $t->keputusan_pakar ?: 'SEHAT NORMAL',
                'catatan_pakar'     => $t->catatan_pakar ?: 'Kondisi fisik normal.',
                'harga_jual'        => (float) ($t->harga_jual ?? 0),
                'pembeli'           => $t->pembeli ?? '-',
                'tgl_terjual'       => $t->tgl_terjual ?? '-',
                'tgl_kematian'      => $t->tgl_kematian ?? '-',
                'penyebab_kematian' => $t->penyebab_kematian ?? '-',
                'tgl_pendataan'     => $t->tgl_pendataan ?? $t->created_at?->format('Y-m-d') ?? '-',
                'riwayat_timbang'   => $t->riwayatTimbangs->map(function ($r) {
                    return [
                        'id'          => $r->id,
                        'berat_kg'    => (float) $r->berat_kg,
                        'selisih_kg'  => (float) $r->selisih_kg,
                        'sumber_alat' => $r->sumber_alat,
                        'waktu'       => Carbon::parse($r->tgl_timbang)->format('d M Y, H:i'),
                    ];
                })
            ];
        });

        // Hitung stats yang terpisah akurat
        $totalTernak     = $semuaTernak->count();
        $totalTerjual    = count($idTerjual);
        $totalMati       = count($idMati);
        $totalAktif      = max(0, $totalTernak - $totalTerjual - $totalMati);
        $omzetPenjualan  = (float) $semuaTernak->whereIn('id', $idTerjual)->sum('harga_jual');

        return Inertia::render('Operator/GudangArsip', [
            'arsip'          => $arsipTernak,
            'current_filter' => $statusFilter,
            'stats'          => [
                'total_arsip'       => $totalTernak,
                'total_aktif'       => $totalAktif,
                'total_terjual'     => $totalTerjual,
                'total_mati'        => $totalMati,
                'omzet_penjualan'   => $omzetPenjualan,
                'mortalitas_persen' => $totalTernak > 0 ? round(($totalMati / $totalTernak) * 100, 1) : 0,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $statusFilter = $request->query('status', 'All');

        $query = Ternak::orderByDesc('updated_at');

        if ($statusFilter === 'Terjual') {
            $query->where(function ($q) {
                $q->where('status_hidup', 'Terjual')
                  ->orWhere('program', 'Terjual')
                  ->orWhereNotNull('tgl_terjual')
                  ->orWhere('harga_jual', '>', 0);
            });
        } elseif ($statusFilter === 'Mati') {
            $query->where(function ($q) {
                $q->where('status_hidup', 'Mati')
                  ->orWhere('program', 'Arsip Kematian')
                  ->orWhereNotNull('tgl_kematian')
                  ->orWhereNotNull('penyebab_kematian');
            });
        } elseif ($statusFilter === 'Aktif') {
            $query->where('program', '!=', 'Terjual')
                  ->where('program', '!=', 'Arsip Kematian')
                  ->whereNull('tgl_terjual')
                  ->whereNull('tgl_kematian');
        }

        $ternaks = $query->get();
        $namaFile = "Arsip_Data_Ternak_JJFarm_" . now()->format('Y-m-d_His') . ".csv";

        return new StreamedResponse(function () use ($ternaks) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No', 'Tag RFID', 'Jenis Ras', 'Jenis Kelamin', 'Status Hidup',
                'Program Terakhir', 'Kandang Terakhir', 'Bobot Akhir (Kg)',
                'Kondisi Medis', 'Status Reproduksi', 'Harga Jual (Rp)',
                'Pembeli / Tujuan', 'Tanggal Terjual', 'Tanggal Kematian',
                'Penyebab Kematian', 'Tanggal Registrasi'
            ], ';');

            $nomor = 1;
            foreach ($ternaks as $t) {
                $statusStr = 'Aktif';
                if ($t->program === 'Terjual' || !empty($t->tgl_terjual) || (float)$t->harga_jual > 0) {
                    $statusStr = 'Terjual';
                } elseif (!empty($t->tgl_kematian) || !empty($t->penyebab_kematian) || $t->program === 'Arsip Kematian') {
                    $statusStr = 'Mati';
                }

                fputcsv($handle, [
                    $nomor++,
                    "'" . $t->rfid_tag,
                    $t->ras ?? 'Lokal',
                    $t->jenis_kelamin ?? 'Betina',
                    $statusStr,
                    $t->program ?? '-',
                    $t->kandang ?? '-',
                    str_replace('.', ',', (string) ($t->berat_kg ?? 0)),
                    $t->kondisi_kesehatan ?? 'Sehat',
                    $t->status_kehamilan ?? 'Non-Bunting',
                    $t->harga_jual ? (int) $t->harga_jual : 0,
                    $t->pembeli ?? '-',
                    $t->tgl_terjual ?? '-',
                    $t->tgl_kematian ?? '-',
                    $t->penyebab_kematian ?? '-',
                    $t->tgl_pendataan ?? $t->created_at?->format('Y-m-d') ?? '-',
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$namaFile}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }
}