<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ternak;
use App\Models\RiwayatTimbang;
use App\Models\SecurityLog;
use App\Events\TelemetriBaruEvent; // Event WebSocket Reverb
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TernakApiController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Fleksibel (Mendukung Payload Bersarang ESP32 maupun Flat)
        $rfid = $request->input('telemetri.rfid_tag') ?? $request->input('rfid_tag');
        $berat = $request->input('telemetri.berat_kg') ?? $request->input('berat_kg');

        if (!$rfid || $berat === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal: RFID Tag dan Berat Badan wajib disertakan!'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $selisih   = (float) ($request->input('telemetri.selisih_kg') ?? $request->input('selisih_kg', 0.0));
            $deviceId  = $request->input('device_info.device_id') ?? 'ESP32_TIMBANGAN_01';
            $kandang   = $request->input('profil_ternak.kandang') ?? $request->input('kandang', 'Blok A');
            $keputusan = $request->input('validasi_pakar.keputusan_pakar') ?? $request->input('keputusan_pakar', 'SEHAT NORMAL');

            $rawTime = $request->input('device_info.timestamp') ?? now()->toTimeString();
            $waktuTimbang = (strlen($rawTime) <= 8)
                ? Carbon::today()->format('Y-m-d') . ' ' . $rawTime
                : Carbon::parse($rawTime)->format('Y-m-d H:i:s');

            // Ringkas pemeriksaan klinis jika ada
            $klinis = [];
            if ($request->filled('pemeriksaan_klinis')) {
                foreach ($request->input('pemeriksaan_klinis') as $k => $v) {
                    $klinis[] = ucfirst(str_replace('_', ' ', $k)) . ': ' . $v;
                }
            }
            $gejalaSakit = count($klinis) > 0 ? implode(' | ', $klinis) : $request->input('gejala_sakit');

            // 2. Simpan atau Update Master Ternak
            $ternak = Ternak::updateOrCreate(
                ['rfid_tag' => $rfid],
                [
                    'ras'               => $request->input('profil_ternak.ras') ?? $request->input('ras'),
                    'jenis_kelamin'     => $request->input('profil_ternak.jenis_kelamin') ?? $request->input('jenis_kelamin', 'Jantan'),
                    'bapak'             => $request->input('profil_ternak.pejantan') ?? $request->input('bapak'),
                    'mamak'             => $request->input('profil_ternak.betina') ?? $request->input('mamak'),
                    'jumlah_anak'       => $request->input('profil_ternak.jumlah_anak') ?? $request->input('jumlah_anak', 0),
                    'tgl_lahir'         => $request->input('profil_ternak.tgl_lahir') ?? $request->input('tgl_lahir'),
                    'tgl_pendataan'     => Carbon::today()->toDateString(),
                    'kandang'           => $kandang,
                    'program'           => $request->input('profil_ternak.program') ?? $request->input('program', 'Penggemukan'),
                    'status_kehamilan'  => $request->input('profil_ternak.status_kehamilan') ?? $request->input('status_kehamilan', 'N/A'),
                    'berat_kg'          => (float) $berat,
                    'selisih_kg'        => $selisih,
                    'status_hidup'      => true,
                    'kondisi_kesehatan' => $keputusan,
                    'gejala_sakit'      => $gejalaSakit,
                    'prediksi_ai'       => $request->input('validasi_pakar.prediksi_ai') ?? $request->input('prediksi_ai'),
                    'keputusan_pakar'   => $keputusan,
                    'catatan_pakar'     => $request->input('validasi_pakar.catatan_pakar') ?? $request->input('catatan_pakar'),
                ]
            );

            // 3. Simpan Riwayat Timbangan
            $riwayat = RiwayatTimbang::create([
                'ternak_id'   => $ternak->id,
                'berat_kg'    => (float) $berat,
                'selisih_kg'  => $selisih,
                'sumber_alat' => $deviceId,
                'tgl_timbang' => $waktuTimbang,
            ]);

            // 4. Catat Otomatis ke Security Logs Super Admin (Audit Trail)
            SecurityLog::create([
                'actor'  => 'Sistem ESP32 (' . $deviceId . ')',
                'role'   => 'Hardware IoT',
                'action' => 'DATA_IN',
                'desc'   => "Menerima telemetri RFID {$rfid} ({$berat} Kg, selisih {$selisih} Kg) di lokasi {$kandang}",
                'status' => 'Success'
            ]);

            DB::commit();

            // 5. Pancarkan Event Reverb Real-time
            broadcast(new TelemetriBaruEvent($ternak, $riwayat));

            return response()->json([
                'status'  => 'success',
                'message' => 'Data ternak berhasil disinkronkan dan jejak sistem tercatat!',
                'data'    => $ternak
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses data ternak: ' . $e->getMessage()
            ], 500);
        }
    }
}