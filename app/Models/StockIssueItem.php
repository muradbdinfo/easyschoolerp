<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIssueItem extends Model
{
    protected $fillable = [
        'stock_issue_request_id','item_id',
        'quantity_requested','quantity_issued','unit','notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_issued'    => 'decimal:2',
    ];

    public function stockIssueRequest(): BelongsTo { return $this->belongsTo(StockIssueRequest::class); }
    public function item(): BelongsTo              { return $this->belongsTo(Item::class); }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity_requested - (float)$this->quantity_issued);
    }
}