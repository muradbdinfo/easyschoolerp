<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'item_id',
        'item_code',
        'item_name',
        'item_description',
        'unit',
        'quantity',
        'quantity_approved',
        'estimated_unit_price',
        'estimated_total',
        'actual_unit_price',
        'actual_total',
        'specifications',
        'notes',
        'budget_line_id',
        'purchase_order_item_id',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'actual_unit_price' => 'decimal:2',
        'actual_total' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate estimated total
            $item->estimated_total = $item->quantity * $item->estimated_unit_price;
        });

        static::saved(function ($item) {
            // Update parent PR total
            $item->purchaseRequisition->calculateTotal();
            $item->purchaseRequisition->save();
        });

        static::deleted(function ($item) {
            // Update parent PR total
            $item->purchaseRequisition->calculateTotal();
            $item->purchaseRequisition->save();
        });
    }

    /**
     * Relationships
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Accessors
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->estimated_total, 2);
    }
}