<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ternak;
use App\Models\Kandang;
use Carbon\Carbon;

class KarantinaController extends Controller
{
    public function index()
    {
        $kandangs = Kandang::all();

        $semuaTernak = Ternak::where('status_hidup', 'Aktif')
            ->orderBy('rfid_tag')
            ->get(['id', 'rfid_tag', 'ras', 'jenis_kelamin', 'kandang', 'berat_kg', 'kondisi_kesehatan', 'program']);

        // Tangkap ternak yang sakit, karantina, suspect, atau belum 100% sembuh
        $pasienKarantina = Ternak::where('status_hidup', 'Aktif')
            ->where(function ($q) {
                $q->where('kondisi_kesehatan', 'Sakit')
                  ->orWhere('kondisi_kesehatan', 'Karantina')
                  ->orWhere('kondisi_kesehatan', 'like', '%SAKIT%')
                  ->orWhere('kondisi_kesehatan', 'like', '%ISOLASI%')
                  ->orWhere('kondisi_kesehatan', 'like', '%KARANTINA%')
                  ->orWhere('prediksi_ai', 'SUSPECT')
                  ->orWhere('persentase_sembuh', '<', 100)
                  ->orWhere('kandang', 'KRT-01')
                  ->orWhere('kandang', 'like', '%karantina%');
            })
            ->orderBy('persentase_sembuh', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($t) {
                $hariDirawat = 1;
                if ($t->tgl_mulai_sakit) {
                    $mulai = Carbon::parse($t->tgl_mulai_sakit)->startOfDay();
                    $hariDirawat = max(1, (int) $mulai->diffInDays(Carbon::now()->startOfDay()) + 1);
                }

                return [
                    'id'                => $t->id,
                    'rfid_tag'          => $t->rfid_tag,
                    'ras'               => $t->ras ?? 'Lokal',
                    'jenis_kelamin'     => $t->jenis_kelamin ?? 'Betina',
                    'berat_kg'          => (float) ($t->berat_kg ?? 0),
                    'kandang'           => $t->kandang ?? 'KRT-01',
                    'kondisi_kesehatan' => $t->kondisi_kesehatan ?? 'Karantina',
                    'persentase_sembuh' => (int) ($t->persentase_sembuh ?? 30),
                    'gejala_sakit'      => $t->gejala_sakit ?: 'Gejala fisik belum terdokumentasi lengkap',
                    'penanganan_medis'  => $t->penanganan_medis ?: 'Pemberian multivitamin & pemisahan pakan',
                    'tgl_mulai_sakit'   => $t->tgl_mulai_sakit ?: now()->toDateString(),
                    'hari_dirawat'      => $hariDirawat,
                    'catatan_pakar'     => $t->catatan_pakar ?: 'Dalam pengawasan intensif karantina',
                    'keputusan_pakar'   => $t->keputusan_pakar ?: 'KARANTINA / ISOLASI',
                ];
            });

        $kandangKarantina = Kandang::where('kode_kandang', 'KRT-01')
            ->orWhere('tipe_kandang', 'Karantina')
            ->orWhere('nama_kandang', 'like', '%karantina%')
            ->first();

        $kapasitasKrt = $kandangKarantina ? (int) $kandangKarantina->kapasitas : 15;

        return Inertia::render('Operator/KarantinaMedis', [
            'pasien'       => $pasienKarantina,
            'semua_ternak' => $semuaTernak,
            'kandangs'     => $kandangs,
            'stats'        => [
                'total_pasien'  => $pasienKarantina->count(),
                'fase_kritis'   => $pasienKarantina->where('persentase_sembuh', '<', 50)->count(),
                'menuju_pulih'  => $pasienKarantina->where('persentase_sembuh', '>=', 50)->where('persentase_sembuh', '<', 100)->count(),
                'kapasitas_krt' => $kapasitasKrt,
            ]
        ]);
    }

    public function laporSakit(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'gejala_sakit'      => 'required|string|max:500',
            'penanganan_medis'  => 'required|string|max:500',
            'persentase_sembuh' => 'required|integer|min:0|max:95',
            'pindah_karantina'  => 'nullable|boolean',
        ]);

        $updateData = [
            'kondisi_kesehatan' => 'Karantina',
            'persentase_sembuh' => $validated['persentase_sembuh'],
            'gejala_sakit'      => $validated['gejala_sakit'],
            'penanganan_medis'  => $validated['penanganan_medis'],
            'tgl_mulai_sakit'   => now()->toDateString(),
            'keputusan_pakar'   => 'KARANTINA / ISOLASI',
            'catatan_pakar'     => 'Pasien isolasi darurat: ' . $validated['gejala_sakit'],
        ];

        if (!empty($validated['pindah_karantina'])) {
            $updateData['kandang']           = 'KRT-01';
            $updateData['tgl_masuk_kandang'] = now()->toDateString();
            $updateData['program']           = 'Karantina';
        }

        $ternak->update($updateData);

        return redirect()->back();
    }

    public function updateProgres(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'persentase_sembuh'   => 'required|integer|min:0|max:95',
            'penanganan_tambahan' => 'nullable|string|max:500',
            'catatan_evaluasi'    => 'nullable|string|max:500',
        ]);

        $catatanMedis = $ternak->penanganan_medis ?? '';
        if (!empty($validated['penanganan_tambahan'])) {
            $catatanMedis .= ($catatanMedis ? ' | ' : '') . "[Update " . now()->format('d/m') . "]: " . $validated['penanganan_tambahan'];
        }

        $ternak->update([
            'persentase_sembuh' => $validated['persentase_sembuh'],
            'penanganan_medis'  => $catatanMedis,
            'catatan_pakar'     => $validated['catatan_evaluasi'] ?: $ternak->catatan_pakar,
        ]);

        return redirect()->back();
    }

    public function verifikasiSembuh(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'kandang_tujuan'    => 'required|string|max:100',
            'program_tujuan'    => 'required|string|max:100',
            'catatan_pelepasan' => 'nullable|string|max:500',
        ]);

        $ternak->update([
            'kondisi_kesehatan' => 'Sehat',
            'persentase_sembuh' => 100,
            'gejala_sakit'      => null,
            'penanganan_medis'  => null,
            'tgl_mulai_sakit'   => null,
            'kandang'           => $validated['kandang_tujuan'],
            'tgl_masuk_kandang' => now()->toDateString(),
            'program'           => $validated['program_tujuan'],
            'keputusan_pakar'   => 'SEHAT NORMAL',
            'catatan_pakar'     => 'Dinyatakan sembuh oleh dokter/pakar. Catatan: ' . ($validated['catatan_pelepasan'] ?: 'Kondisi fisik prima & aktif.'),
        ]);

        return redirect()->back();
    }
}