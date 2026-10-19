<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ternak extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfid_tag',
        'ras',
        'jenis_kelamin',
        'bapak',
        'mamak',
        'jumlah_anak',
        'daftar_anak',
        'tgl_lahir',
        'tgl_pendataan',
        'kandang',
        'tgl_masuk_kandang',
        'berat_kg',
        'selisih_kg',
        'harga_jual',
        'tgl_terjual',
        'pembeli',
        'status_hidup',
        'tgl_kematian',
        'penyebab_kematian',
        'program',
        'status_kehamilan',
        'kondisi_kesehatan',
        'persentase_sembuh',
        'gejala_sakit',
        'penanganan_medis',
        'tgl_mulai_sakit',
        'prediksi_ai',
        'keputusan_pakar',
        'catatan_pakar',
    ];

    protected $casts = [
        'daftar_anak'       => 'array',
        'status_hidup'      => 'string', // Diubah dari 'boolean' menjadi 'string'
        'berat_kg'          => 'decimal:2',
        'selisih_kg'        => 'decimal:2',
        'harga_jual'        => 'decimal:2',
        'persentase_sembuh' => 'integer',
        'tgl_lahir'         => 'date:Y-m-d',
        'tgl_pendataan'     => 'date:Y-m-d',
        'tgl_masuk_kandang' => 'date:Y-m-d',
        'tgl_terjual'       => 'date:Y-m-d',
        'tgl_kematian'      => 'date:Y-m-d',
        'tgl_mulai_sakit'   => 'date:Y-m-d',
    ];

    public function riwayatTimbangs()
    {
        return $this->hasMany(RiwayatTimbang::class, 'ternak_id')->orderByDesc('tgl_timbang');
    }
}