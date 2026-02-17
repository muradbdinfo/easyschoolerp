<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'head_id',
        'annual_budget',
        'spent_amount',
        'approval_threshold',
        'is_active',
        'phone',
        'email',
        'location',
    ];

    protected $casts = [
        'annual_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'is_active' => 'boolean',
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

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessors
     */
    public function getBudgetRemainingAttribute(): float
    {
        return $this->annual_budget - $this->spent_amount;
    }

    public function getBudgetUtilizationPercentageAttribute(): float
    {
        if ($this->annual_budget == 0) {
            return 0;
        }
        return ($this->spent_amount / $this->annual_budget) * 100;
    }
}