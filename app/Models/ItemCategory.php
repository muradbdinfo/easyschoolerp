<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = self::generateCode();
            }
        });
    }

    /**
     * Generate unique category code
     */
    public static function generateCode(): string
    {
        $lastCategory = self::withTrashed()->latest('id')->first();
        $number = $lastCategory ? intval(substr($lastCategory->code, 4)) + 1 : 1;
        return 'CAT-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ItemCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Helper methods
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $names);
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }
}