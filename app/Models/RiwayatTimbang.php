<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatTimbang extends Model
{
    use HasFactory;

    // Menegaskan nama tabel di database
    protected $table = 'riwayat_timbangs';

    protected $fillable = [
        'ternak_id',
        'berat_kg',
        'selisih_kg',
        'sumber_alat',
        'tgl_timbang',
    ];

    protected $casts = [
        'berat_kg'    => 'decimal:2',
        'selisih_kg'  => 'decimal:2',
        'tgl_timbang' => 'datetime',
    ];

    public function ternak()
    {
        return $this->belongsTo(Ternak::class, 'ternak_id');
    }
}