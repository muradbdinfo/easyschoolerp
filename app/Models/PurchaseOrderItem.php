<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'item_id', 'purchase_requisition_item_id',
        'item_name', 'unit', 'quantity', 'unit_price', 'received_quantity', 'specifications',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    protected $appends = ['total_price', 'pending_quantity'];

    public function getTotalPriceAttribute(): float
    {
        return (float)$this->quantity * (float)$this->unit_price;
    }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity - (float)$this->received_quantity);
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function ($item) {
            optional($item->purchaseOrder)->recalculateTotals();
        });
    }

    public function purchaseOrder(): BelongsTo  { return $this->belongsTo(PurchaseOrder::class); }
    public function item(): BelongsTo           { return $this->belongsTo(Item::class); }
    public function prItem(): BelongsTo         { return $this->belongsTo(PurchaseRequisitionItem::class, 'purchase_requisition_item_id'); }
}