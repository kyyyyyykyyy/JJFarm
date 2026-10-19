<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kandang;
use App\Models\Ternak;
use Carbon\Carbon;

class KandangController extends Controller
{
    public function index()
    {
        // 1. AUTO-SEED: Jika database kandang masih kosong (0), buatkan otomatis 8 unit kandang standar lapangan
        if (Kandang::count() === 0) {
            $defaultKandangs = [
                [
                    'kode_kandang' => 'Blok A',
                    'nama_kandang' => 'Kandang Blok A (IoT Timbangan)',
                    'tipe_kandang' => 'Penggemukan',
                    'kapasitas'    => 30,
                    'keterangan'   => 'Penempatan utama ternak timbangan ESP32'
                ],
                [
                    'kode_kandang' => 'Blok B',
                    'nama_kandang' => 'Kandang Blok B (Indukan Bunting)',
                    'tipe_kandang' => 'Bunting',
                    'kapasitas'    => 25,
                    'keterangan'   => 'Indukan bunting dan masa persiapan lahir'
                ],
                [
                    'kode_kandang' => 'KRT-01',
                    'nama_kandang' => 'Kandang KRT-01 (Karantina Medis)',
                    'tipe_kandang' => 'Karantina',
                    'kapasitas'    => 15,
                    'keterangan'   => 'Isolasi ternak sakit/suspect dan tindakan darurat'
                ],
                [
                    'kode_kandang' => 'Kandang A1',
                    'nama_kandang' => 'Kandang A1 (Kawin / Pemacakan)'
                    'tipe_kandang' => 'Kawin',
                    'kapasitas'    => 20,
                    'keterangan'   => 'Siklus perkawinan indukan dan pejantan trah unggul'
                ],
                [
                    'kode_kandang' => 'Kandang B1',
                    'nama_kandang' => 'Kandang B1 (Bunting Hasil USG)',
                    'tipe_kandang' => 'Bunting',
                    'kapasitas'    => 25,
                    'keterangan'   => 'Perawatan nutrisi kebuntingan trimester'
                ],
                [
                    'kode_kandang' => 'Kandang C1',
                    'nama_kandang' => 'Kandang C1 (Melahirkan / Partus)',
                    'tipe_kandang' => 'Melahirkan',
                    'kapasitas'    => 10,
                    'keterangan'   => 'Sekat steril indukan melahirkan'
                ],
                [
                    'kode_kandang' => 'Kandang D1',
                    'nama_kandang' => 'Kandang D1 (Menyusui / Laktasi)',
                    'tipe_kandang' => 'Menyusui',
                    'kapasitas'    => 15,
                    'keterangan'   => 'Indukan dan cempe masa menyusui'
                ],
                [
                    'kode_kandang' => 'Kandang E1',
                    'nama_kandang' => 'Kandang E1 (Sapih / Cempe Lepas)',
                    'tipe_kandang' => 'Sapih',
                    'kapasitas'    => 35,
                    'keterangan'   => 'Fase pembesaran mandiri cempe'
                ],
            ];

            foreach ($defaultKandangs as $dk) {
                Kandang::create($dk);
            }
        }

        // 2. Ambil seluruh data ternak aktif
        $ternakAktif = Ternak::where('status_hidup', 'Aktif')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($t) {
                $hariDiKandang = 1;
                if ($t->tgl_masuk_kandang) {
                    $masuk = Carbon::parse($t->tgl_masuk_kandang)->startOfDay();
                    $hariDiKandang = max(1, (int) $masuk->diffInDays(Carbon::now()->startOfDay()) + 1);
                }

                return [
                    'id'                => $t->id,
                    'rfid_tag'          => $t->rfid_tag,
                    'ras'               => $t->ras ?? 'Lokal',
                    'jenis_kelamin'     => $t->jenis_kelamin ?? 'Betina',
                    'berat_kg'          => (float) ($t->berat_kg ?? 0),
                    'selisih_kg'        => (float) ($t->selisih_kg ?? 0),
                    'kandang'           => $t->kandang ?? 'Blok A',
                    'tgl_masuk_kandang' => $t->tgl_masuk_kandang ? Carbon::parse($t->tgl_masuk_kandang)->format('Y-m-d') : now()->toDateString(),
                    'hari_di_kandang'   => $hariDiKandang,
                    'program'           => $t->program ?? 'Produksi',
                    'status_kehamilan'  => $t->status_kehamilan ?? 'Non-Bunting',
                    'kondisi_kesehatan' => $t->kondisi_kesehatan ?? 'Sehat',
                    'gejala_sakit'      => $t->gejala_sakit,
                    'catatan_pakar'     => $t->catatan_pakar,
                    'keputusan_pakar'   => $t->keputusan_pakar,
                    'prediksi_ai'       => $t->prediksi_ai,
                    'persentase_sembuh' => (int) ($t->persentase_sembuh ?? 100),
                ];
            });

        // 3. Petakan penghuni ke setiap kotak kandang secara fleksibel
        $kandangs = Kandang::orderBy('kode_kandang')->get()->map(function ($k) use ($ternakAktif) {
            $penghuni = $ternakAktif->filter(function ($t) use ($k) {
                $tKandang = strtolower(trim($t['kandang'] ?? ''));
                $kKode    = strtolower(trim($k->kode_kandang ?? ''));
                $kNama    = strtolower(trim($k->nama_kandang ?? ''));

                return $tKandang === $kKode || 
                       $tKandang === $kNama || 
                       ($tKandang !== '' && str_contains($kNama, $tKandang)) ||
                       ($kKode !== '' && str_contains($tKandang, $kKode));
            })->values();

            $kapasitas       = max(1, (int) $k->kapasitas);
            $totalEkor       = $penghuni->count();
            $persenKapasitas = min(100, round(($totalEkor / $kapasitas) * 100, 0));

            return [
                'id'               => $k->id,
                'kode_kandang'     => $k->kode_kandang,
                'nama_kandang'     => $k->nama_kandang,
                'tipe_kandang'     => $k->tipe_kandang,
                'kapasitas'        => $kapasitas,
                'keterangan'       => $k->keterangan ?? '-',
                'total_ekor'       => $totalEkor,
                'persen_kapasitas' => $persenKapasitas,
                'penghuni'         => $penghuni
            ];
        });

        // 4. Hitung notifikasi siklus dan alert SOP
        $notifikasiSiklus = [];

        foreach ($ternakAktif as $t) {
            $kandangName = strtolower($t['kandang']);
            $hari        = $t['hari_di_kandang'];
            
            // Mendeteksi status bunting dari variasi kata (HAMIL / BUNTING)
            $isHamil = str_contains(strtoupper($t['status_kehamilan']), 'HAMIL') || 
                       str_contains(strtoupper($t['status_kehamilan']), 'BUNTING');
            
            // Mendeteksi status perawatan medis (SAKIT / KARANTINA)
            $isSakit = str_contains(strtoupper($t['kondisi_kesehatan']), 'SAKIT') || 
                       str_contains(strtoupper($t['kondisi_kesehatan']), 'KARANTINA');

            // A. Indukan Bunting tapi belum di Kandang Bunting
            if ($isHamil && !str_contains($kandangName, 'bunting') && !str_contains($kandangName, 'b1') && !str_contains($kandangName, 'blok b')) {
                $notifikasiSiklus[] = [
                    'id'                  => 'alert-hamil-' . $t['id'],
                    'level'               => 'warning',
                    'stage'               => 'Perhatian Kebuntingan',
                    'judul'               => "Indukan Hamil di {$t['kandang']} ({$t['rfid_tag']})",
                    'pesan'               => "Indukan {$t['rfid_tag']} terverifikasi BUNTING namun masih berada di {$t['kandang']}. Segera mutasikan ke Kandang Bunting (Blok B / B1).",
                    'rekomendasi_kandang' => 'Blok B',
                    'ternak'              => $t
                ];
            }

            // B. Ternak Sakit / Karantina di luar Karantina
            if ($isSakit && !str_contains($kandangName, 'krt') && !str_contains($kandangName, 'karantina')) {
                $notifikasiSiklus[] = [
                    'id'                  => 'alert-sakit-' . $t['id'],
                    'level'               => 'danger',
                    'stage'               => 'Darurat Medis',
                    'judul'               => "Ternak Sakit di Kandang Umum ({$t['rfid_tag']})",
                    'pesan'               => "Ternak {$t['rfid_tag']} sedang sakit / karantina di {$t['kandang']}. Segera isolasi ke Kandang KRT-01.",
                    'rekomendasi_kandang' => 'KRT-01',
                    'ternak'              => $t
                ];
            }

            // C. SOP Waktu Tinggal
            if ((str_contains($kandangName, 'a1') || str_contains($kandangName, 'kawin')) && $hari >= 36) {
                $notifikasiSiklus[] = [
                    'id'                  => 'alert-kawin-' . $t['id'],
                    'level'               => 'warning',
                    'stage'               => 'Siklus Kawin (A1)',
                    'judul'               => "Waktunya USG Kebuntingan ({$t['rfid_tag']})",
                    'pesan'               => "Indukan {$t['rfid_tag']} telah {$hari} hari di Kandang Kawin. Segera jadwalkan USG.",
                    'rekomendasi_kandang' => 'Kandang B1',
                    'ternak'              => $t
                ];
            }

            if ((str_contains($kandangName, 'bunting') || str_contains($kandangName, 'blok b')) && $hari >= 100) {
                $notifikasiSiklus[] = [
                    'id'                  => 'alert-bunting-' . $t['id'],
                    'level'               => 'danger',
                    'stage'               => 'Masa Bunting Akhir',
                    'judul'               => "Persiapan Melahirkan ({$t['rfid_tag']})",
                    'pesan'               => "Indukan {$t['rfid_tag']} sudah {$hari} hari di Kandang Bunting. Pindahkan ke Kandang Melahirkan (C1).",
                    'rekomendasi_kandang' => 'Kandang C1',
                    'ternak'              => $t
                ];
            }
        }

        return Inertia::render('Operator/PipelineKandang', [
            'kandangs'          => $kandangs,
            'notifikasi_siklus' => $notifikasiSiklus,
            'semua_ternak'      => $ternakAktif,
            'stats'             => [
                'total_kandang'      => $kandangs->count(),
                'total_populasi'     => $ternakAktif->count(),
                'total_kapasitas'    => $kandangs->sum('kapasitas'),
                'total_alert_siklus' => count($notifikasiSiklus),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kandang' => 'required|string|max:50|unique:kandangs,kode_kandang',
            'nama_kandang' => 'required|string|max:100',
            'tipe_kandang' => 'required|in:Kawin,Bunting,Melahirkan,Menyusui,Sapih,Penggemukan,Karantina,Umum',
            'kapasitas'    => 'required|integer|min:1',
            'keterangan'   => 'nullable|string|max:255',
        ]);

        Kandang::create($validated);
        return redirect()->back();
    }

    public function update(Request $request, Kandang $kandang)
    {
        $validated = $request->validate([
            'kode_kandang' => 'required|string|max:50|unique:kandangs,kode_kandang,' . $kandang->id,
            'nama_kandang' => 'required|string|max:100',
            'tipe_kandang' => 'required|in:Kawin,Bunting,Melahirkan,Menyusui,Sapih,Penggemukan,Karantina,Umum',
            'kapasitas'    => 'required|integer|min:1',
            'keterangan'   => 'nullable|string|max:255',
        ]);

        if ($kandang->kode_kandang !== $validated['kode_kandang']) {
            Ternak::where('kandang', $kandang->kode_kandang)->update(['kandang' => $validated['kode_kandang']]);
        }

        $kandang->update($validated);
        return redirect()->back();
    }

    public function destroy(Kandang $kandang)
    {
        $cekTernak = Ternak::where('status_hidup', 'Aktif')
            ->where(function ($q) use ($kandang) {
                $q->where('kandang', $kandang->kode_kandang)
                  ->orWhere('kandang', $kandang->nama_kandang);
            })->count();

        if ($cekTernak > 0) {
            return redirect()->back()->withErrors([
                'error' => "Kandang {$kandang->nama_kandang} masih berisi {$cekTernak} ekor ternak! Pindahkan ternak terlebih dahulu sebelum menghapus kandang."
            ]);
        }

        $kandang->delete();
        return redirect()->back();
    }

    public function mutasiKandang(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'kandang_tujuan'    => 'required|string|max:100',
            'program_tujuan'    => 'nullable|string|max:100',
            'tgl_masuk_kandang' => 'nullable|date',
            'catatan'           => 'nullable|string|max:255',
        ]);

        $tglMasuk = $request->filled('tgl_masuk_kandang') 
            ? $request->input('tgl_masuk_kandang') 
            : now()->toDateString();

        $updateData = [
            'kandang'           => $validated['kandang_tujuan'],
            'tgl_masuk_kandang' => $tglMasuk,
        ];

        if (!empty($validated['program_tujuan'])) {
            $updateData['program'] = $validated['program_tujuan'];
        }

        if (!empty($validated['catatan'])) {
            $updateData['catatan_pakar'] = $validated['catatan'];
        }

        $ternak->update($updateData);
        return redirect()->back();
    }
}