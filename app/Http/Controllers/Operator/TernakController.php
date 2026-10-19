<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\Kandang;
use App\Models\RiwayatTimbang;
use Carbon\Carbon;

class TernakController extends Controller
{
    /**
     * Tampilan Utama Katalog Data Ternak & Notifikasi Lapangan
     */
    public function index()
    {
        $kandangs = Kandang::all();

        $semuaTernak = Ternak::where('status_hidup', 'Aktif')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($t) {
                $umur = '-';
                if ($t->tgl_lahir) {
                    $diff = Carbon::parse($t->tgl_lahir)->diff(Carbon::now());
                    $umur = $diff->y > 0 ? "{$diff->y} Thn {$diff->m} Bln" : "{$diff->m} Bulan";
                }

                // Hitung hari tinggal di kandang terkini
                $hariDiKandang = 1;
                if ($t->tgl_masuk_kandang) {
                    $hariDiKandang = max(1, Carbon::parse($t->tgl_masuk_kandang)->diffInDays(Carbon::now()) + 1);
                }

                // Format daftar_anak aman (array atau string)
                $daftarAnakStr = '';
                if (is_array($t->daftar_anak)) {
                    $daftarAnakStr = implode(', ', $t->daftar_anak);
                } elseif (is_string($t->daftar_anak)) {
                    $daftarAnakStr = $t->daftar_anak;
                }

                return [
                    'id'                => $t->id,
                    'rfid_tag'          => $t->rfid_tag,
                    'ras'               => $t->ras ?? 'Lokal',
                    'jenis_kelamin'     => $t->jenis_kelamin ?? 'Betina',
                    'bapak'             => $t->bapak ?: 'Non-Identified',
                    'mamak'             => $t->mamak ?: 'Non-Identified',
                    'jumlah_anak'       => (int) ($t->jumlah_anak ?? 0),
                    'daftar_anak'       => $daftarAnakStr,
                    'tgl_lahir'         => $t->tgl_lahir ?? '',
                    'umur'              => $umur,
                    'tgl_pendataan'     => $t->tgl_pendataan ?? '',
                    'kandang'           => $t->kandang ?? 'Kandang A1',
                    'hari_di_kandang'   => $hariDiKandang,
                    'berat_kg'          => (float) ($t->berat_kg ?? 0),
                    'selisih_kg'        => (float) ($t->selisih_kg ?? 0),
                    'program'           => $t->program ?? 'Produksi',
                    'status_kehamilan'  => $t->status_kehamilan ?? 'Non-Bunting',
                    'kondisi_kesehatan' => $t->kondisi_kesehatan ?? 'Sehat',
                    'persentase_sembuh' => (int) ($t->persentase_sembuh ?? 100),
                    'gejala_sakit'      => $t->gejala_sakit ?? '',
                    'penanganan_medis'  => $t->penanganan_medis ?? '',
                    'tgl_mulai_sakit'   => $t->tgl_mulai_sakit ?? '',
                    'prediksi_ai'       => $t->prediksi_ai ?? 'HEALTHY',
                    'keputusan_pakar'   => $t->keputusan_pakar ?? 'SEHAT NORMAL',
                    'catatan_pakar'     => $t->catatan_pakar ?? 'Pemeriksaan fisik normal, ternak sehat.',
                ];
            });

        // NOTIFIKASI PINTAR OTOMATIS
        $notifikasi = [];

        // 1. Alert Medis (Sakit / Karantina / Suspect / Belum Pulih)
        $ternakSakit = $semuaTernak->filter(function ($t) {
            $kondisi = strtoupper($t['kondisi_kesehatan'] ?? '');
            $isSakit = str_contains($kondisi, 'SAKIT') || str_contains($kondisi, 'KARANTINA') || str_contains($kondisi, 'ISOLASI');
            $isSuspect = strtoupper($t['prediksi_ai'] ?? '') === 'SUSPECT';
            return $isSakit || $isSuspect || $t['persentase_sembuh'] < 100;
        });

        foreach ($ternakSakit as $s) {
            $notifikasi[] = [
                'level' => 'danger',
                'judul' => 'Ternak Dalam Perawatan Medis / Karantina',
                'pesan' => "Tag {$s['rfid_tag']} ({$s['ras']}) di {$s['kandang']}. Status: {$s['kondisi_kesehatan']} ({$s['persentase_sembuh']}%). Gejala: " . ($s['gejala_sakit'] ?: 'Perlu pemantauan rutin'),
                'rfid'  => $s['rfid_tag']
            ];
        }

        // 2. Alert Siklus Kawin >= 36 Hari
        $butuhUsg = $semuaTernak->filter(fn($t) => str_contains(strtolower($t['kandang']), 'a1') && $t['hari_di_kandang'] >= 36);
        foreach ($butuhUsg as $u) {
            $notifikasi[] = [
                'level' => 'warning',
                'judul' => 'Waktunya Pemeriksaan USG Kebuntingan',
                'pesan' => "Indukan {$u['rfid_tag']} telah {$u['hari_di_kandang']} hari di Kandang Kawin. Segera jadwalkan USG.",
                'rfid'  => $u['rfid_tag']
            ];
        }

        // 3. Alert Bobot Siap Jual (>= 30 Kg dan Tidak Sakit / Tidak Karantina)
        $siapJual = $semuaTernak->filter(function ($t) {
            $kondisi = strtoupper($t['kondisi_kesehatan'] ?? '');
            $tidakSakit = !str_contains($kondisi, 'SAKIT') && !str_contains($kondisi, 'KARANTINA');
            return $t['berat_kg'] >= 30 && $tidakSakit;
        });

        if ($siapJual->count() > 0) {
            $notifikasi[] = [
                'level' => 'success',
                'judul' => 'Kambing Siap Masuk Katalog Penjualan',
                'pesan' => "Terdapat {$siapJual->count()} ekor ternak dengan bobot prima (>= 30 Kg) siap dijual.",
                'rfid'  => null
            ];
        }

        // Deteksi Total Bunting Fleksibel (Hamil / Bunting)
        $totalBuntingCount = $semuaTernak->filter(function ($t) {
            $repro = strtoupper($t['status_kehamilan'] ?? '');
            return str_contains($repro, 'HAMIL') || str_contains($repro, 'BUNTING');
        })->count();

        return Inertia::render('Operator/KatalogTernak', [
            'ternaks'    => $semuaTernak->values(),
            'kandangs'   => $kandangs,
            'notifikasi' => $notifikasi,
            'stats'      => [
                'total_aktif'   => $semuaTernak->count(),
                'total_sakit'   => $ternakSakit->count(),
                'total_bunting' => $totalBuntingCount,
                'siap_panen'    => $siapJual->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid_tag'         => 'required|string|unique:ternaks,rfid_tag',
            'ras'              => 'required|string|max:100',
            'jenis_kelamin'    => 'required|in:Jantan,Betina',
            'bapak'            => 'nullable|string|max:100',
            'mamak'            => 'nullable|string|max:100',
            'jumlah_anak'      => 'nullable|integer|min:0',
            'daftar_anak'      => 'nullable|string|max:255',
            'tgl_lahir'        => 'nullable|date',
            'tgl_pendataan'    => 'required|date',
            'kandang'          => 'required|string|max:100',
            'berat_kg'         => 'required|numeric|min:1',
            'selisih_kg'       => 'nullable|numeric',
            'program'          => 'required|string|max:100',
            'status_kehamilan' => 'nullable|string|max:100',
            'keputusan_pakar'  => 'nullable|string|max:255',
            'catatan_pakar'    => 'nullable|string|max:500',
        ]);

        $validated['status_hidup'] = 'Aktif';
        $validated['tgl_masuk_kandang'] = now()->toDateString();
        $validated['kondisi_kesehatan'] = 'Sehat';
        $validated['persentase_sembuh'] = 100;
        $validated['prediksi_ai'] = $validated['keputusan_pakar'] ?: 'HEALTHY';

        $ternak = Ternak::create($validated);

        RiwayatTimbang::create([
            'ternak_id'   => $ternak->id,
            'berat_kg'    => $validated['berat_kg'],
            'selisih_kg'  => $validated['selisih_kg'] ?? 0,
            'sumber_alat' => 'Registrasi Awal Operator',
            'tgl_timbang' => now(),
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'rfid_tag'         => 'required|string|unique:ternaks,rfid_tag,' . $ternak->id,
            'ras'              => 'required|string|max:100',
            'jenis_kelamin'    => 'required|in:Jantan,Betina',
            'bapak'            => 'nullable|string|max:100',
            'mamak'            => 'nullable|string|max:100',
            'jumlah_anak'      => 'nullable|integer|min:0',
            'daftar_anak'      => 'nullable|string|max:255',
            'tgl_lahir'        => 'nullable|date',
            'tgl_pendataan'    => 'required|date',
            'kandang'          => 'required|string|max:100',
            'berat_kg'         => 'required|numeric|min:1',
            'program'          => 'required|string|max:100',
            'status_kehamilan' => 'nullable|string|max:100',
            'keputusan_pakar'  => 'nullable|string|max:255',
            'catatan_pakar'    => 'nullable|string|max:500',
        ]);

        $beratLama = (float) $ternak->berat_kg;
        $beratBaru = (float) $validated['berat_kg'];
        
        if (abs($beratBaru - $beratLama) > 0.001) {
            $selisih = round($beratBaru - $beratLama, 2);
            $validated['selisih_kg'] = $selisih;

            RiwayatTimbang::create([
                'ternak_id'   => $ternak->id,
                'berat_kg'    => $beratBaru,
                'selisih_kg'  => $selisih,
                'sumber_alat' => 'Pembaruan Timbangan Lapangan',
                'tgl_timbang' => now(),
            ]);
        }

        if ($ternak->kandang !== $validated['kandang']) {
            $validated['tgl_masuk_kandang'] = now()->toDateString();
        }

        $ternak->update($validated);

        return redirect()->back();
    }

    public function tanggapMedis(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'kondisi_kesehatan' => 'required|in:Sehat,Sakit,Karantina',
            'persentase_sembuh' => 'required|integer|min:0|max:100',
            'gejala_sakit'      => 'required_if:kondisi_kesehatan,Sakit,Karantina|nullable|string|max:500',
            'penanganan_medis'  => 'required_if:kondisi_kesehatan,Sakit,Karantina|nullable|string|max:500',
            'pindah_karantina'  => 'nullable|boolean',
        ]);

        $updateData = [
            'kondisi_kesehatan' => $validated['kondisi_kesehatan'],
            'persentase_sembuh' => $validated['persentase_sembuh'],
            'gejala_sakit'      => $validated['gejala_sakit'],
            'penanganan_medis'  => $validated['penanganan_medis'],
            'catatan_pakar'     => "Medis: " . ($validated['penanganan_medis'] ?: 'Pemeriksaan rutin'),
        ];

        if ($validated['kondisi_kesehatan'] !== 'Sehat' && !$ternak->tgl_mulai_sakit) {
            $updateData['tgl_mulai_sakit'] = now()->toDateString();
        }

        if (!empty($validated['pindah_karantina'])) {
            $updateData['kandang'] = 'KRT-01';
            $updateData['tgl_masuk_kandang'] = now()->toDateString();
            $updateData['program'] = 'Karantina';
        }

        if ($validated['persentase_sembuh'] == 100 && $validated['kondisi_kesehatan'] === 'Sehat') {
            $updateData['kondisi_kesehatan'] = 'Sehat';
            $updateData['gejala_sakit'] = null;
            $updateData['penanganan_medis'] = null;
            $updateData['tgl_mulai_sakit'] = null;
            $updateData['program'] = 'Penggemukan';
        }

        $ternak->update($updateData);

        return redirect()->back();
    }

    public function destroy(Request $request, Ternak $ternak)
    {
        $penyebab = $request->input('penyebab_kematian', 'Kematian Alami / Sakit');

        $ternak->update([
            'status_hidup'      => 'Mati',
            'tgl_kematian'      => now()->toDateString(),
            'penyebab_kematian' => $penyebab,
            'program'           => 'Arsip Kematian',
        ]);

        return redirect()->back();
    }
}