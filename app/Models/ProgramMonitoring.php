<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKelompok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramMonitoring extends Model
{
    use BelongsToKelompok;

    protected $table = 'program_monitoring';

    protected $fillable = ['kelompok_id', 'nama_program', 'target_peserta', 'tenggat', 'status', 'dibuat_oleh'];

    protected function casts(): array
    {
        return ['tenggat' => 'date'];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProgramMonitoringItem::class);
    }
}
