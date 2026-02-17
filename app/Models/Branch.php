<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'head_id',
        'address',
        'city',
        'district',
        'postal_code',
        'country',
        'phone',
        'email',
        'fax',
        'latitude',
        'longitude',
        'established_date',
        'is_active',
        'is_main_branch',
        'student_capacity',
        'staff_count',
        'annual_budget',
        'settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'established_date' => 'date',
        'is_active' => 'boolean',
        'is_main_branch' => 'boolean',
        'annual_budget' => 'decimal:2',
        'settings' => 'array',
    ];

    /**
     * Relationships
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMainBranch($query)
    {
        return $query->where('is_main_branch', true);
    }

    /**
     * Accessors
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->district,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}