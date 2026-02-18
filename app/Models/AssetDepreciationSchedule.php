<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciationSchedule extends Model
{
    protected $fillable = [
        'asset_id', 'year', 'month',
        'opening_value', 'depreciation_amount', 'closing_value',
        'processed_date', 'processed_by',
    ];

    protected $casts = [
        'opening_value'        => 'decimal:2',
        'depreciation_amount'  => 'decimal:2',
        'closing_value'        => 'decimal:2',
        'processed_date'       => 'date',
    ];

    public function asset(): BelongsTo       { return $this->belongsTo(Asset::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}