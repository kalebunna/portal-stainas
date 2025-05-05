<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'nama_kampus',
        'singkatan',
        'deskripsi',
        'logo',
        'alamat',
        'telepon',
        'email',
        'website',
        'visi',
        'misi',
        'akreditasi',
        'sejarah',
        'maps_embed',
        'rektor_name',
        'rektor_photo',
        'sambutan_rektor',
    ];
}
