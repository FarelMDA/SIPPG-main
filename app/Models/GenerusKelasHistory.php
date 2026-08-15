<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerusKelasHistory extends Model
{
    public $timestamps = false;

    protected $table = 'generus_kelas_histories';

    protected $fillable = ['generus_id', 'kelas_id', 'semester', 'dicatat_oleh'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
