<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kerjasama extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_instansi',
        'slug',
        'deskripsi',
        'jenis',
        'logo',
        'tanggal_mulai',
        'tanggal_selesai',
        'manfaat',
        'dokumen',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];
}
