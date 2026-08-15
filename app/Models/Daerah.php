<?php

namespace App\Models;

use App\Models\Concerns\HasAbbreviationDefault;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Daerah extends Model
{
    use HasAbbreviationDefault, HasFactory;

    protected $table = 'daerah';

    protected $fillable = ['nama', 'abbreviation', 'alamat_sekretariat', 'visi', 'misi'];

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class);
    }
}
