<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = [
        'judul',
        'slug',
        'deskripsi',
        'lokasi',
        'waktu_mulai',
        'waktu_selesai',
        'gambar',
        'user_id',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
