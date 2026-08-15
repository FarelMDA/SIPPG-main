<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramMonitoringItem extends Model
{
    protected $table = 'program_monitoring_item';

    protected $fillable = ['program_monitoring_id', 'generus_id', 'temuan', 'pic', 'status_item', 'tenggat_item'];

    protected function casts(): array
    {
        return ['tenggat_item' => 'date'];
    }

    public function programMonitoring(): BelongsTo
    {
        return $this->belongsTo(ProgramMonitoring::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }
}
