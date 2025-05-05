<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nim',
        'nama',
        'email',
        'telepon',
        'alamat',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
        'agama',
        'prodi_id',
        'user_id',
        'angkatan',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function karyaMahasiswas()
    {
        return $this->hasMany(Karya_mahasiswa::class);
    }
}
