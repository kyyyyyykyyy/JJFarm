<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ternak;
use App\Models\RiwayatTimbang;
use App\Models\SecurityLog;
use App\Events\TelemetriBaruEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TimbanganApiController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input: kembalikan 400 Bad Request jika RFID kosong atau JSON tidak valid
        $validator = Validator::make($request->all(), [
            'telemetri.rfid_tag' => 'required|string',
            'telemetri.berat_kg' => 'nullable|numeric',
            'telemetri.selisih_kg' => 'nullable|numeric',

            'device_info.device_id' => 'nullable|string',
            'device_info.timestamp' => 'nullable|string',

            'profil_ternak.ras'              => 'nullable|string',
            'profil_ternak.jenis_kelamin'    => 'nullable|string',
            'profil_ternak.pejantan'         => 'nullable|string',
            'profil_ternak.betina'           => 'nullable|string',
            'profil_ternak.jumlah_anak'      => 'nullable|integer',
            'profil_ternak.tgl_lahir'        => 'nullable|string', // Boleh string kosong ""
            'profil_ternak.kandang'          => 'nullable|string',
            'profil_ternak.program'          => 'nullable|string',
            'profil_ternak.status_kehamilan' => 'nullable|string',

            'pemeriksaan_klinis.perilaku'    => 'nullable|string',
            'pemeriksaan_klinis.mata_mukosa' => 'nullable|string',
            'pemeriksaan_klinis.bulu_kulit'  => 'nullable|string',
            'pemeriksaan_klinis.pencernaan'  => 'nullable|string',
            'pemeriksaan_klinis.pernapasan'  => 'nullable|string',

            'validasi_pakar.prediksi_ai'     => 'nullable|string',
            'validasi_pakar.keputusan_pakar' => 'nullable|string',
            'validasi_pakar.catatan_pakar'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format payload tidak valid atau RFID Tag kosong.',
                'errors'  => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();

            $waktuServer = Carbon::now(); // Waktu resmi server (received_at)
            $rfid        = (string) $request->input('telemetri.rfid_tag');
            $berat       = (float) $request->input('telemetri.berat_kg', 0.0);
            $selisih     = (float) $request->input('telemetri.selisih_kg', 0.0);
            $deviceId    = (string) $request->input('device_info.device_id', 'ESP32_TIMBANGAN_01');
            $espUptime   = (string) $request->input('device_info.timestamp', '');
            $aiPred      = $request->input('validasi_pakar.prediksi_ai', 'HEALTHY');
            $keputusan   = $request->input('validasi_pakar.keputusan_pakar', 'SEHAT NORMAL');

            // Tangani tgl_lahir jika dikirim string kosong ""
            $rawTglLahir = $request->input('profil_ternak.tgl_lahir');
            $tglLahir    = !empty(trim($rawTglLahir ?? '')) ? $rawTglLahir : null;

            // Mapping ENUM kondisi_kesehatan ('Sehat', 'Sakit', 'Karantina')
            $kondisiKesehatan = 'Sehat';
            $upperKeputusan   = strtoupper((string) $keputusan);
            if (str_contains($upperKeputusan, 'KARANTINA') || str_contains($upperKeputusan, 'ISOLASI')) {
                $kondisiKesehatan = 'Karantina';
            } elseif (str_contains($upperKeputusan, 'SAKIT') || str_contains($upperKeputusan, 'SUSPECT')) {
                $kondisiKesehatan = 'Sakit';
            }

            // Rangkum pemeriksaan klinis
            $klinis = [];
            if ($request->filled('pemeriksaan_klinis') && is_array($request->input('pemeriksaan_klinis'))) {
                foreach ($request->input('pemeriksaan_klinis') as $k => $v) {
                    if (!empty($v)) {
                        $klinis[] = ucfirst(str_replace('_', ' ', $k)) . ': ' . $v;
                    }
                }
            }
            $gejalaSakit = count($klinis) > 0 ? implode(' | ', $klinis) : 'Pemeriksaan rutin normal';

            // 2. Logika Upsert Master Ternak berdasarkan rfid_tag
            $ternak = Ternak::firstOrNew(['rfid_tag' => $rfid]);

            $ternak->ras               = $request->input('profil_ternak.ras') ?: ($ternak->ras ?? 'Garut / Texel');
            $ternak->jenis_kelamin     = $request->input('profil_ternak.jenis_kelamin') ?: ($ternak->jenis_kelamin ?? 'Jantan');
            $ternak->bapak             = $request->input('profil_ternak.pejantan') ?: ($ternak->bapak ?? 'Tag #001');
            $ternak->mamak             = $request->input('profil_ternak.betina') ?: ($ternak->mamak ?? 'Tag #089');
            $ternak->jumlah_anak       = (int) $request->input('profil_ternak.jumlah_anak', $ternak->jumlah_anak ?? 0);
            $ternak->tgl_lahir         = $tglLahir ?: ($ternak->tgl_lahir ?? '2024-05-10');
            $ternak->tgl_pendataan     = $waktuServer->toDateString();
            $ternak->kandang           = $request->input('profil_ternak.kandang') ?: ($ternak->kandang ?? 'Blok A');
            $ternak->program           = $request->input('profil_ternak.program') ?: ($ternak->program ?? 'Penggemukan');
            $ternak->status_kehamilan  = $request->input('profil_ternak.status_kehamilan') ?: ($ternak->status_kehamilan ?? 'N/A');

            // Update parameter berat jika ada pembacaan
            if ($berat > 0) {
                $ternak->berat_kg   = $berat;
                $ternak->selisih_kg = $selisih;
            }

            $ternak->status_hidup      = 'Aktif';
            $ternak->kondisi_kesehatan = $kondisiKesehatan;
            $ternak->gejala_sakit      = $gejalaSakit;
            $ternak->prediksi_ai       = $aiPred;
            $ternak->keputusan_pakar   = $keputusan;
            $ternak->catatan_pakar     = $request->input('validasi_pakar.catatan_pakar') ?: ($ternak->catatan_pakar ?? '');

            $ternak->save();

            // 3. Simpan Riwayat Timbangan Baru jika bobot valid
            $riwayat = null;
            if ($berat > 0) {
                $riwayat = RiwayatTimbang::create([
                    'ternak_id'   => $ternak->id,
                    'berat_kg'    => $berat,
                    'selisih_kg'  => $selisih,
                    'sumber_alat' => $deviceId . ($espUptime ? " (Uptime: {$espUptime})" : ''),
                    'tgl_timbang' => $waktuServer,
                ]);
            }

            // 4. Catat ke Security Log
            SecurityLog::create([
                'actor'  => "Gateway {$deviceId}",
                'role'   => 'Hardware IoT',
                'action' => 'DATA_IN',
                'desc'   => "RFID {$rfid} ({$berat} Kg) diterima server pada {$waktuServer->toTimeString()}",
                'status' => 'Success'
            ]);

            DB::commit();

            // 5. Broadcast Reverb (Try-Catch Isolasi agar web tetap kirim 200 OK)
            try {
                if (class_exists(TelemetriBaruEvent::class) && $riwayat) {
                    broadcast(new TelemetriBaruEvent($ternak, $riwayat));
                }
            } catch (\Throwable $socketEx) {
                Log::warning('Peringatan: Gagal broadcast Reverb: ' . $socketEx->getMessage());
            }

            // Respons 200 OK sesuai spesifikasi
            return response()->json([
                'status'      => 'success',
                'message'     => 'Data telemetri berhasil diproses dan disimpan.',
                'received_at' => $waktuServer->toDateTimeString(),
                'rfid_tag'    => $ternak->rfid_tag,
                'data'        => [
                    'ternak_id'   => $ternak->id,
                    'rfid_tag'    => $ternak->rfid_tag,
                    'berat_kg'    => $ternak->berat_kg,
                    'selisih_kg'  => $ternak->selisih_kg,
                    'sumber_alat' => $riwayat ? $riwayat->sumber_alat : $deviceId,
                    'waktu'       => $waktuServer->toDateTimeString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store telemetri: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal merekam telemetri: ' . $e->getMessage()
            ], 500);
        }
    }
}