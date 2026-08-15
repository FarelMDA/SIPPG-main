<?php

namespace App\Models;

use App\Models\Concerns\HasAbbreviationDefault;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelompok extends Model
{
    use HasAbbreviationDefault, HasFactory, SoftDeletes;

    protected $table = 'kelompok';

    protected $fillable = ['desa_id', 'nama', 'abbreviation', 'alamat'];

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function rombonganBelajar(): HasMany
    {
        return $this->hasMany(RombonganBelajar::class);
    }

    public function pendidik(): HasMany
    {
        return $this->hasMany(Pendidik::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function musyawaroh(): HasMany
    {
        return $this->hasMany(Musyawaroh::class);
    }

    public function sensusSnapshots(): HasMany
    {
        return $this->hasMany(SensusSnapshot::class);
    }

    public function programMonitoring(): HasMany
    {
        return $this->hasMany(ProgramMonitoring::class);
    }

    public function laporanBulanan(): HasMany
    {
        return $this->hasMany(LaporanBulanan::class);
    }
}
