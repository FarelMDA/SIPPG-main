<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RombonganBelajar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rombongan_belajar';

    protected $fillable = ['kelompok_id', 'nama', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function jenjangs(): BelongsToMany
    {
        return $this->belongsToMany(Jenjang::class, 'rombongan_belajar_jenjang')->orderBy('urutan');
    }
}
