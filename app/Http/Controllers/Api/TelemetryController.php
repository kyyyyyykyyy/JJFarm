<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ternak;
use App\Models\RiwayatTimbang;
use App\Events\TelemetriBaruEvent; // Event WebSocket Reverb
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelemetryController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Payload Bersarang dari ESP32-P4
        $request->validate([
            'device_info.device_id' => 'required|string',
            'device_info.timestamp' => 'required|string',
            'telemetri.rfid_tag'   => 'required|string',
            'telemetri.berat_kg'   => 'required|numeric',
            'telemetri.selisih_kg' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $rfid      = $request->input('telemetri.rfid_tag');
            $berat     = (float) $request->input('telemetri.berat_kg');
            $selisih   = (float) $request->input('telemetri.selisih_kg', 0.0);
            $deviceId  = $request->input('device_info.device_id');
            $keputusan = $request->input('validasi_pakar.keputusan_pakar', 'SEHAT NORMAL');

            // Format waktu timbang
            $rawTime = $request->input('device_info.timestamp');
            $waktuTimbang = (strlen($rawTime) <= 8)
                ? Carbon::today()->format('Y-m-d') . ' ' . $rawTime
                : Carbon::parse($rawTime)->format('Y-m-d H:i:s');

            // Ringkas 5 cek klinis
            $klinis = [];
            if ($request->filled('pemeriksaan_klinis')) {
                foreach ($request->input('pemeriksaan_klinis') as $k => $v) {
                    $klinis[] = ucfirst(str_replace('_', ' ', $k)) . ': ' . $v;
                }
            }
            $ringkasanKlinis = implode(' | ', $klinis);

            // 2. UPDATE atau CREATE Master Profil Ternak
            $ternak = Ternak::updateOrCreate(
                ['rfid_tag' => $rfid],
                [
                    'ras'               => $request->input('profil_ternak.ras'),
                    'jenis_kelamin'     => $request->input('profil_ternak.jenis_kelamin', 'Jantan'),
                    'bapak'             => $request->input('profil_ternak.pejantan'),
                    'mamak'             => $request->input('profil_ternak.betina'),
                    'jumlah_anak'       => $request->input('profil_ternak.jumlah_anak', 0),
                    'tgl_lahir'         => $request->input('profil_ternak.tgl_lahir'),
                    'tgl_pendataan'     => Carbon::today()->toDateString(),
                    'kandang'           => $request->input('profil_ternak.kandang'),
                    'program'           => $request->input('profil_ternak.program', 'Penggemukan'),
                    'status_kehamilan'  => $request->input('profil_ternak.status_kehamilan', 'N/A'),
                    'berat_kg'          => $berat,
                    'selisih_kg'        => $selisih,
                    'status_hidup'      => true,
                    'kondisi_kesehatan' => $keputusan,
                    'gejala_sakit'      => $ringkasanKlinis,
                    'prediksi_ai'       => $request->input('validasi_pakar.prediksi_ai'),
                    'keputusan_pakar'   => $keputusan,
                    'catatan_pakar'     => $request->input('validasi_pakar.catatan_pakar'),
                ]
            );

            // 3. INSERT Riwayat Baru ke Tabel riwayat_timbangs
            $riwayat = RiwayatTimbang::create([
                'ternak_id'   => $ternak->id,
                'berat_kg'    => $berat,
                'selisih_kg'  => $selisih,
                'sumber_alat' => $deviceId,
                'tgl_timbang' => $waktuTimbang,
            ]);

            DB::commit();

            // 4. Pancarkan Sinyal WebSocket Reverb ke Frontend Vue
            broadcast(new TelemetriBaruEvent($ternak, $riwayat));

            return response()->json([
                'status'  => 'success',
                'message' => 'Data telemetri sinkron dan log berhasil disimpan!',
                'data'    => [
                    'ternak_id'   => $ternak->id,
                    'rfid_tag'    => $ternak->rfid_tag,
                    'berat_kg'    => $ternak->berat_kg,
                    'selisih_kg'  => $ternak->selisih_kg,
                    'sumber_alat' => $riwayat->sumber_alat,
                    'waktu'       => $riwayat->tgl_timbang->toDateTimeString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses telemetri: ' . $e->getMessage()
            ], 500);
        }
    }
}