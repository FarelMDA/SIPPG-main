<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerusStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'generus_status_histories';

    protected $fillable = ['generus_id', 'status_domisili', 'berlaku_sejak', 'dicatat_oleh'];

    protected function casts(): array
    {
        return [
            'berlaku_sejak' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
