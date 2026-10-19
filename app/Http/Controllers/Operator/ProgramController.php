<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\Kandang;
use App\Models\RiwayatTimbang;
use Carbon\Carbon;

class ProgramController extends Controller
{
    public function index()
    {
        $kandangs = Kandang::all();

        $ternakAktif = Ternak::where('status_hidup', 'Aktif')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($t) {
                $diffHari = 1;
                if ($t->tgl_masuk_kandang) {
                    $diffHari = max(1, Carbon::parse($t->tgl_masuk_kandang)->diffInDays(Carbon::now()) + 1);
                }

                return [
                    'id'                => $t->id,
                    'rfid_tag'          => $t->rfid_tag,
                    'ras'               => $t->ras ?? 'Lokal',
                    'jenis_kelamin'     => $t->jenis_kelamin ?? 'Betina',
                    'kandang'           => $t->kandang ?? 'Kandang Utama',
                    'hari_di_kandang'   => $diffHari,
                    'berat_kg'          => (float) ($t->berat_kg ?? 0),
                    'selisih_kg'        => (float) ($t->selisih_kg ?? 0),
                    'program'           => $t->program ?? 'Produksi',
                    'status_kehamilan'  => $t->status_kehamilan ?? 'Non-Bunting',
                    'kondisi_kesehatan' => $t->kondisi_kesehatan ?? 'Sehat',
                    'catatan_pakar'     => $t->catatan_pakar ?? 'Pemeriksaan rutin normal.',
                ];
            });

        $kelompokPembibitan = $ternakAktif->filter(function ($t) {
            $k = strtolower($t['kandang']);
            return $t['jenis_kelamin'] === 'Betina' || 
                   $t['program'] === 'Pembibitan' || 
                   $t['program'] === 'Produksi' || 
                   str_contains($k, 'a1') || 
                   str_contains($k, 'b1');
        })->values();

        $kelompokPenggemukan = $ternakAktif->filter(function ($t) {
            return $t['program'] === 'Penggemukan' || 
                   $t['program'] === 'Siap Jual' || 
                   $t['berat_kg'] >= 25;
        })->values();

        $riwayatTimbangTerbaru = RiwayatTimbang::with('ternak')
            ->orderByDesc('tgl_timbang')
            ->take(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id'          => $r->id,
                    'rfid_tag'    => $r->ternak->rfid_tag ?? '-',
                    'ras'         => $r->ternak->ras ?? '-',
                    'berat_kg'    => (float) $r->berat_kg,
                    'selisih_kg'  => (float) $r->selisih_kg,
                    'sumber_alat' => $r->sumber_alat,
                    'waktu'       => Carbon::parse($r->tgl_timbang)->format('d M Y, H:i'),
                ];
            });

        // Deteksi Bunting Fleksibel (Hamil / Bunting / Trimester)
        $totalBuntingCount = $kelompokPembibitan->filter(function ($t) {
            $s = strtoupper($t['status_kehamilan'] ?? '');
            return str_contains($s, 'HAMIL') || str_contains($s, 'BUNTING');
        })->count();

        return Inertia::render('Operator/ProgramKerja', [
            'pembibitan'      => $kelompokPembibitan,
            'penggemukan'     => $kelompokPenggemukan,
            'kandangs'        => $kandangs,
            'riwayat_timbang' => $riwayatTimbangTerbaru,
            'stats'           => [
                'total_bibit'    => $kelompokPembibitan->count(),
                'total_bunting'  => $totalBuntingCount,
                'butuh_usg'      => $kelompokPembibitan->filter(fn($t) => str_contains(strtolower($t['kandang']), 'a1') && $t['hari_di_kandang'] >= 36)->count(),
                'total_gemuk'    => $kelompokPenggemukan->count(),
                'siap_panen'     => $kelompokPenggemukan->where('program', 'Siap Jual')->count() + $kelompokPenggemukan->where('berat_kg', '>=', 30)->where('program', '!=', 'Siap Jual')->count(),
                'ready_katalog'  => $kelompokPenggemukan->where('program', 'Siap Jual')->count(),
                'avg_gain_gemuk' => round($kelompokPenggemukan->avg('selisih_kg') ?? 0, 2),
            ]
        ]);
    }

    public function submitUsg(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'status_kehamilan' => 'required|string|max:100',
            'catatan_usg'      => 'nullable|string|max:500',
            'pindah_kandang'   => 'nullable|boolean',
        ]);

        $statusUpper = strtoupper($validated['status_kehamilan']);
        $isBunting   = str_contains($statusUpper, 'HAMIL') || str_contains($statusUpper, 'BUNTING');

        $updateData = [
            'status_kehamilan' => $validated['status_kehamilan'],
            'catatan_pakar'    => 'Hasil USG: ' . ($validated['catatan_usg'] ?: ($isBunting ? 'Positif Bunting' : 'Negatif Bunting')),
        ];

        if ($isBunting && !empty($validated['pindah_kandang'])) {
            $updateData['kandang']           = 'Kandang B1';
            $updateData['tgl_masuk_kandang'] = now()->toDateString();
            $updateData['program']           = 'Pembibitan';
        }

        $ternak->update($updateData);
        return redirect()->back();
    }

    public function storeTimbang(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'berat_kg'    => 'required|numeric|min:1',
            'sumber_alat' => 'nullable|string|max:100',
        ]);

        $beratLama = (float) $ternak->berat_kg;
        $beratBaru = (float) $validated['berat_kg'];
        $selisih   = round($beratBaru - $beratLama, 2);

        RiwayatTimbang::create([
            'ternak_id'   => $ternak->id,
            'berat_kg'    => $beratBaru,
            'selisih_kg'  => $selisih,
            'sumber_alat' => $validated['sumber_alat'] ?: 'Timbangan Digital Lapangan',
            'tgl_timbang' => now(),
        ]);

        $updateTernak = [
            'berat_kg'      => $beratBaru,
            'selisih_kg'    => $selisih,
            'tgl_pendataan' => now()->toDateString(),
        ];

        if ($beratBaru >= 30 && $ternak->program === 'Penggemukan') {
            $updateTernak['program'] = 'Siap Jual';
        }

        $ternak->update($updateTernak);
        return redirect()->back();
    }

    /**
     * RILIS TERNAK KE KATALOG SIAP JUAL (KONEK LANGSUNG KE OWNER)
     */
    public function rilisSiapJual(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'berat_kg'      => 'required|numeric|min:1',
            'grade_katalog' => 'required|string|max:100',
            'kandang'       => 'required|string|max:100',
            'catatan'       => 'nullable|string|max:500',
        ]);

        $beratLama = (float) $ternak->berat_kg;
        $beratBaru = (float) $validated['berat_kg'];
        $selisih   = round($beratBaru - $beratLama, 2);

        // Catat timbangan final
        if (abs($beratBaru - $beratLama) > 0.001) {
            RiwayatTimbang::create([
                'ternak_id'   => $ternak->id,
                'berat_kg'    => $beratBaru,
                'selisih_kg'  => $selisih,
                'sumber_alat' => 'Verifikasi Final Siap Jual',
                'tgl_timbang' => now(),
            ]);
        }

        $ternak->update([
            'berat_kg'          => $beratBaru,
            'selisih_kg'        => $selisih,
            'program'           => 'Siap Jual', // Status ini langsung terbit di menu Owner Proyeksi Panen
            'kandang'           => $validated['kandang'],
            'tgl_masuk_kandang' => now()->toDateString(),
            'catatan_pakar'     => "[Grade: {$validated['grade_katalog']}] " . ($validated['catatan'] ?: 'Lolos kriteria panen & siap ditransaksikan.'),
        ]);

        return redirect()->back();
    }
}