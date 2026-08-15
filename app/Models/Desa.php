<?php

namespace App\Models;

use App\Models\Concerns\HasAbbreviationDefault;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Desa extends Model
{
    use HasAbbreviationDefault, HasFactory, SoftDeletes;

    protected $table = 'desa';

    protected $fillable = ['daerah_id', 'nama', 'abbreviation'];

    public function daerah(): BelongsTo
    {
        return $this->belongsTo(Daerah::class);
    }

    public function kelompok(): HasMany
    {
        return $this->hasMany(Kelompok::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
