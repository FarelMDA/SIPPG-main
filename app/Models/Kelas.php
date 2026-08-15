<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = ['kelompok_id', 'jenjang_id', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    /** Nama selalu = label Jenjang, tidak pernah dikustomisasi per-Kelompok — bukan kolom tersimpan. */
    protected function nama(): Attribute
    {
        return Attribute::get(fn () => $this->jenjang->label);
    }

    /** Urutan pedagogis (PAUD A → ... → GPN-B) — pengganti orderBy('nama') sejak nama jadi accessor. */
    public function scopeUrutJenjang($query)
    {
        return $query->orderBy(Jenjang::select('urutan')->whereColumn('jenjang.id', 'kelas.jenjang_id'));
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function generus(): HasMany
    {
        return $this->hasMany(Generus::class);
    }

    public function pendidik(): BelongsToMany
    {
        return $this->belongsToMany(Pendidik::class, 'pendidik_kelas');
    }

    /** Kategori usia ringkas (PAUD-A/PAUD-B/ACR/APR/AR/GPN-A/GPN-B) untuk sensus (SRS §7, PRD §9.2). */
    public function kategoriUsia(): string
    {
        return $this->jenjang->kategori_usia;
    }
}
