<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $fillable = [
        'nama',
        'slug',
        'jenjang',
        'kode',
        'deskripsi',
        'visi',
        'misi',
        'akreditasi',
        'gelar',
        'icon',
        'gambar',
        'ketua_prodi',
        'kompetensi',
        'kurikulum',
        'prospek_kerja',
        'durasi_tahun',
    ];

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class);
    }
}
