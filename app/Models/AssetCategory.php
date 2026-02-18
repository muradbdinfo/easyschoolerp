<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'parent_id',
        'depreciation_method', 'depreciation_rate',
        'useful_life_years', 'residual_value_percent',
        'status', 'created_by',
    ];

    protected $casts = [
        'depreciation_rate'      => 'decimal:2',
        'residual_value_percent' => 'decimal:2',
        'status'                 => 'boolean',
    ];

    protected $appends = ['depreciation_method_label'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cat) {
            if (empty($cat->code)) {
                $cat->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $last = self::withTrashed()->latest('id')->first();
        $num  = $last ? intval(substr($last->code, 3)) + 1 : 1;
        return 'AC-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AssetCategory::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    public function getDepreciationMethodLabelAttribute(): string
    {
        return match($this->depreciation_method) {
            'slm'  => 'Straight Line (SLM)',
            'wdv'  => 'Written Down Value (WDV)',
            'none' => 'No Depreciation',
            default => ucfirst((string) ($this->depreciation_method ?? 'Unknown')),
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}