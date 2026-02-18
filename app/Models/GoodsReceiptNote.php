<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'goods_receipt_notes';

    protected $fillable = [
        'grn_number', 'purchase_order_id', 'vendor_id', 'branch_id',
        'receipt_date', 'received_by',
        'supplier_invoice_no', 'supplier_delivery_note', 'vehicle_number',
        'overall_status', 'quality_checked_by', 'quality_remarks',
        'photos', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'photos'       => 'array',
    ];

    protected $appends = ['status_badge'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($grn) {
            if (!$grn->grn_number) {
                $grn->grn_number = static::generateGRNNumber();
            }
        });
    }

    public static function generateGRNNumber(): string
    {
        $prefix = 'GRN-' . now()->format('Y-m') . '-';
        $last = static::where('grn_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq  = $last ? ((int) substr($last->grn_number, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->overall_status) {
            'passed'  => ['label' => 'Passed',  'severity' => 'success'],
            'failed'  => ['label' => 'Failed',  'severity' => 'danger'],
            'partial' => ['label' => 'Partial', 'severity' => 'warning'],
            default   => ['label' => $this->overall_status, 'severity' => 'info'],
        };
    }

    // Relations
    public function purchaseOrder(): BelongsTo  { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor(): BelongsTo          { return $this->belongsTo(Vendor::class); }
    public function branch(): BelongsTo          { return $this->belongsTo(Branch::class); }
    public function receiver(): BelongsTo        { return $this->belongsTo(User::class, 'received_by'); }
    public function qualityChecker(): BelongsTo  { return $this->belongsTo(User::class, 'quality_checked_by'); }
    public function creator(): BelongsTo         { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany             { return $this->hasMany(GrnItem::class, 'grn_id'); }
}