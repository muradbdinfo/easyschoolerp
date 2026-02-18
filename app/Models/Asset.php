<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_tag', 'name', 'category_id', 'item_id',
        'acquisition_date', 'acquisition_cost', 'vendor_id',
        'invoice_number', 'po_number', 'grn_number',
        'brand', 'model_number', 'serial_number', 'color',
        'specifications', 'description',
        'branch_id', 'building', 'floor', 'room', 'location_details',
        'custodian_id', 'custodian_assigned_date',
        'depreciation_method', 'depreciation_rate', 'useful_life_years',
        'residual_value_percent', 'depreciation_start_date',
        'accumulated_depreciation', 'net_book_value',
        'warranty_months', 'warranty_expiry_date', 'warranty_provider',
        'insurance_company', 'insurance_policy_number',
        'insured_value', 'insurance_expiry_date',
        'status', 'condition',
        'primary_photo', 'photos', 'documents', 'qr_code_path',
        'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'acquisition_date'          => 'date',
        'acquisition_cost'          => 'decimal:2',
        'depreciation_rate'         => 'decimal:2',
        'residual_value_percent'    => 'decimal:2',
        'depreciation_start_date'   => 'date',
        'accumulated_depreciation'  => 'decimal:2',
        'net_book_value'            => 'decimal:2',
        'insured_value'             => 'decimal:2',
        'warranty_expiry_date'      => 'date',
        'insurance_expiry_date'     => 'date',
        'custodian_assigned_date'   => 'date',
        'photos'                    => 'array',
        'documents'                 => 'array',
    ];

    protected $appends = [
        'status_badge', 'condition_badge',
        'primary_photo_url', 'warranty_status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asset) {
            if (empty($asset->asset_tag)) {
                $asset->asset_tag = self::generateTag($asset->branch_id, $asset->category_id);
            }
            // Set initial NBV = acquisition cost
            if (empty($asset->net_book_value)) {
                $asset->net_book_value = $asset->acquisition_cost ?? 0;
            }
            // Copy depreciation settings from category if not set
            if ($asset->category_id && empty($asset->depreciation_rate)) {
                $cat = AssetCategory::find($asset->category_id);
                if ($cat) {
                    $asset->depreciation_method        = $cat->depreciation_method;
                    $asset->depreciation_rate          = $cat->depreciation_rate;
                    $asset->useful_life_years          = $cat->useful_life_years;
                    $asset->residual_value_percent     = $cat->residual_value_percent;
                }
            }
        });
    }

    public static function generateTag(?int $branchId = null, ?int $categoryId = null): string
    {
        $branchCode   = 'GEN';
        $categoryCode = 'AST';

        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $branchCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branch->name), 0, 3));
            }
        }

        if ($categoryId) {
            $cat = AssetCategory::find($categoryId);
            if ($cat) {
                $categoryCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cat->name), 0, 3));
            }
        }

        $lastCount = self::withTrashed()->count() + 1;
        return "AS-{$branchCode}-{$categoryCode}-" . str_pad($lastCount, 5, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function category(): BelongsTo    { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function item(): BelongsTo        { return $this->belongsTo(Item::class, 'item_id'); }
    public function vendor(): BelongsTo      { return $this->belongsTo(Vendor::class, 'vendor_id'); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function custodian(): BelongsTo   { return $this->belongsTo(User::class, 'custodian_id'); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'asset_id')->latest();
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class, 'asset_id')->latest();
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(AssetDepreciationSchedule::class, 'asset_id')->orderBy('year')->orderBy('month');
    }

    // Accessors
    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active'            => ['label' => 'Active',           'severity' => 'success'],
            'under_maintenance' => ['label' => 'Under Maintenance', 'severity' => 'warning'],
            'disposed'          => ['label' => 'Disposed',         'severity' => 'secondary'],
            'lost'              => ['label' => 'Lost',             'severity' => 'danger'],
            'damaged'           => ['label' => 'Damaged',          'severity' => 'danger'],
            'written_off'       => ['label' => 'Written Off',      'severity' => 'secondary'],
            default             => ['label' => $this->status,      'severity' => 'info'],
        };
    }

    public function getConditionBadgeAttribute(): array
    {
        return match($this->condition) {
            'excellent' => ['label' => 'Excellent', 'severity' => 'success'],
            'good'      => ['label' => 'Good',      'severity' => 'info'],
            'fair'      => ['label' => 'Fair',      'severity' => 'warning'],
            'poor'      => ['label' => 'Poor',      'severity' => 'danger'],
            default     => ['label' => $this->condition, 'severity' => 'info'],
        };
    }

    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        if ($this->primary_photo) {
            return Storage::disk('public')->url($this->primary_photo);
        }
        return null;
    }

    public function getWarrantyStatusAttribute(): array
    {
        if (!$this->warranty_expiry_date) {
            return ['label' => 'No Warranty', 'severity' => 'secondary'];
        }
        $daysLeft = now()->diffInDays($this->warranty_expiry_date, false);
        if ($daysLeft < 0) {
            return ['label' => 'Expired',       'severity' => 'danger'];
        } elseif ($daysLeft <= 30) {
            return ['label' => "Expires in {$daysLeft}d", 'severity' => 'warning'];
        }
        return ['label' => 'Valid',  'severity' => 'success'];
    }

    // Business logic
    public function calculateAnnualDepreciation(): float
    {
        if ($this->depreciation_method === 'none' || $this->depreciation_rate <= 0) {
            return 0;
        }
        $residualValue = $this->acquisition_cost * ($this->residual_value_percent / 100);
        $depreciableAmount = $this->acquisition_cost - $residualValue;

        if ($this->depreciation_method === 'slm') {
            return $depreciableAmount / max(1, $this->useful_life_years);
        }
        // WDV
        return $this->net_book_value * ($this->depreciation_rate / 100);
    }

    public function calculateMonthlyDepreciation(): float
    {
        return $this->calculateAnnualDepreciation() / 12;
    }

    // Scopes
    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeByBranch($q, $branchId) { return $q->where('branch_id', $branchId); }
    public function scopeByCategory($q, $catId)  { return $q->where('category_id', $catId); }
    public function scopeSearch($q, $search)
    {
        return $q->where(function($sub) use ($search) {
            $sub->where('asset_tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%");
        });
    }
}