<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKelompok;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pendidik extends Model
{
    use BelongsToKelompok, HasFactory;

    protected $table = 'pendidik';

    protected $fillable = ['kelompok_id', 'nama', 'jenis'];

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'pendidik_kelas');
    }
}
