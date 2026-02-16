<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'tax_id',
        'business_registration',
        'bank_details',
        'rating',
        'status',
        'blacklist_reason',
        'blacklisted_at',
        'payment_terms_days',
        'credit_limit',
        'notes',
        'documents',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'credit_limit' => 'decimal:2',
        'blacklisted_at' => 'date',
        'documents' => 'array',
    ];

    protected $appends = [
        'type_label',
        'status_badge',
    ];

    /**
     * Boot the model and auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->code)) {
                $vendor->code = self::generateCode();
            }
        });
    }

    /**
     * Generate unique vendor code
     */
    public static function generateCode(): string
    {
        $lastVendor = self::withTrashed()->latest('id')->first();
        $number = $lastVendor ? intval(substr($lastVendor->code, 4)) + 1 : 1;
        return 'VEN-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'supplier' => 'Supplier',
            'contractor' => 'Contractor',
            'service_provider' => 'Service Provider',
            default => $this->type,
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Active', 'severity' => 'success'],
            'inactive' => ['label' => 'Inactive', 'severity' => 'warning'],
            'blacklisted' => ['label' => 'Blacklisted', 'severity' => 'danger'],
            default => ['label' => $this->status, 'severity' => 'info'],
        };
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('status', 'blacklisted');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Business logic methods
     */
    public function blacklist(string $reason): void
    {
        $this->update([
            'status' => 'blacklisted',
            'blacklist_reason' => $reason,
            'blacklisted_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'blacklist_reason' => null,
            'blacklisted_at' => null,
        ]);
    }

    public function updateRating(float $rating): void
    {
        $this->update(['rating' => max(0, min(5, $rating))]);
    }
}