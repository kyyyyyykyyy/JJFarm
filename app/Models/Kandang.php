<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kandang extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_kandang',
        'nama_kandang',
        'tipe_kandang',
        'kapasitas',
        'keterangan',
    ];

    // Ambil ternak yang aktif berada di kandang ini
    public function ternaks()
    {
        return $this->hasMany(Ternak::class, 'kandang', 'kode_kandang')
                    ->where('status_hidup', 'Aktif');
    }
}