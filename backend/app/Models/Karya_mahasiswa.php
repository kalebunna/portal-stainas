<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karya_mahasiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'slug',
        'deskripsi',
        'mahasiswa_id',
        'user_id',
        'jenis',
        'thumbnail',
        'file',
        'url',
        'is_published',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
