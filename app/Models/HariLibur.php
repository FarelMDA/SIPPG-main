<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kalender Hari Libur Daerah-wide (SRS-Fase-2 §2.6, UC-38). Soft-delete (`dihapus_pada`)
 * hanya semantik untuk baris `sumber=OTOMATIS_GOOGLE` — baris `MANUAL` selalu
 * forceDelete() dari Livewire (§2.6.1), tidak pernah lewat jalur soft-delete ini.
 */
class HariLibur extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hari_libur';

    const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'nama', 'tanggal_mulai', 'tanggal_selesai', 'sumber',
        'google_event_id', 'disunting_manual', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'disunting_manual' => 'boolean',
            'dihapus_pada' => 'datetime',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
