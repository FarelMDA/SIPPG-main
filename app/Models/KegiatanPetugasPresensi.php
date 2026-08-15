<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanPetugasPresensi extends Model
{
    public $timestamps = false;

    protected $table = 'kegiatan_petugas_presensi';

    protected $fillable = [
        'kegiatan_id', 'kelompok_id', 'user_id', 'generus_id',
        'token', 'token_kedaluwarsa', 'ditugaskan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'token_kedaluwarsa' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function ditugaskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function tokenMasihBerlaku(): bool
    {
        return $this->token && $this->token_kedaluwarsa && $this->token_kedaluwarsa->isFuture();
    }
}
