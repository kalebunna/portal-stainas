<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $fillable = [
        'judul',
        'slug',
        'konten',
        'gambar',
        'user_id',
        'is_published',
        'tanggal_mulai',
        'tanggal_selesai',
        'tipe',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
