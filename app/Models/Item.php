<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'type',
        'unit',
        'unit_secondary',
        'conversion_factor',
        'current_price',
        'last_purchase_price',
        'last_purchase_date',
        'avg_price',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
        'reorder_level',
        'lead_time_days',
        'is_consumable',
        'is_asset',
        'asset_threshold_amount',
        'brand',
        'model',
        'manufacturer',
        'specifications',
        'photo',
        'additional_photos',
        'barcode',
        'sku',
        'status',
        'notes',
        'attributes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'avg_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'asset_threshold_amount' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'last_purchase_date' => 'date',
        'is_consumable' => 'boolean',
        'is_asset' => 'boolean',
        'additional_photos' => 'array',
        'attributes' => 'array',
    ];

    protected $appends = [
        'type_label',
        'status_badge',
        'stock_status',
        'photo_url',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->code)) {
                $item->code = self::generateCode();
            }
        });

        static::deleting(function ($item) {
            // Delete photo files when item is deleted
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }
            if ($item->additional_photos) {
                foreach ($item->additional_photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }
        });
    }

    /**
     * Generate unique item code
     */
    public static function generateCode(): string
    {
        $lastItem = self::withTrashed()->latest('id')->first();
        $number = $lastItem ? intval(substr($lastItem->code, 4)) + 1 : 1;
        return 'ITM-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'consumable' => 'Consumable',
            'asset' => 'Asset',
            'both' => 'Both',
            default => $this->type,
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Active', 'severity' => 'success'],
            'inactive' => ['label' => 'Inactive', 'severity' => 'warning'],
            'discontinued' => ['label' => 'Discontinued', 'severity' => 'danger'],
            default => ['label' => $this->status, 'severity' => 'info'],
        };
    }

    public function getStockStatusAttribute(): array
    {
        if ($this->current_stock <= 0) {
            return ['label' => 'Out of Stock', 'severity' => 'danger'];
        } elseif ($this->current_stock <= $this->reorder_level) {
            return ['label' => 'Reorder', 'severity' => 'warning'];
        } elseif ($this->current_stock <= $this->min_stock_level) {
            return ['label' => 'Low Stock', 'severity' => 'warning'];
        } elseif ($this->current_stock >= $this->max_stock_level) {
            return ['label' => 'Overstock', 'severity' => 'info'];
        } else {
            return ['label' => 'In Stock', 'severity' => 'success'];
        }
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return Storage::disk('public')->url($this->photo);
        }
        return null;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConsumable($query)
    {
        return $query->where('is_consumable', true);
    }

    public function scopeAsset($query)
    {
        return $query->where('is_asset', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }

    /**
     * Business logic methods
     */
    public function updateStock(float $quantity, string $operation = 'add'): void
    {
        $newStock = $operation === 'add' 
            ? $this->current_stock + $quantity 
            : $this->current_stock - $quantity;
            
        $this->update(['current_stock' => max(0, $newStock)]);
    }

    public function updatePricing(float $price): void
    {
        // Calculate new average price
        $totalPurchases = $this->requisitionItems()->count();
        $avgPrice = $totalPurchases > 0 
            ? (($this->avg_price ?? 0) * $totalPurchases + $price) / ($totalPurchases + 1)
            : $price;

        $this->update([
            'last_purchase_price' => $price,
            'last_purchase_date' => now(),
            'avg_price' => $avgPrice,
            'current_price' => $price, // Update current price to latest
        ]);
    }

    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }
}