<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id', 'purchase_order_item_id', 'item_id',
        'item_name', 'unit',
        'quantity_ordered', 'quantity_received', 'quantity_accepted', 'quantity_rejected',
        'rejection_reason', 'unit_price', 'assets_created',
    ];

    protected $casts = [
        'quantity_ordered'  => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'unit_price'        => 'decimal:2',
        'assets_created'    => 'boolean',
    ];

    protected $appends = ['total_value', 'needs_assets'];

    public function getTotalValueAttribute(): float
    {
        return (float)$this->quantity_accepted * (float)$this->unit_price;
    }

    public function getNeedsAssetsAttribute(): bool
    {
        return $this->item->is_asset
            && (float)$this->unit_price >= ($this->item->asset_threshold_amount ?? 5000)
            && !$this->assets_created;
    }

    public function grn(): BelongsTo             { return $this->belongsTo(GoodsReceiptNote::class, 'grn_id'); }
    public function item(): BelongsTo            { return $this->belongsTo(Item::class); }
    public function poItem(): BelongsTo          { return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id'); }
}