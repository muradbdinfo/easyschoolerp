<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number', 'po_date', 'purchase_requisition_id', 'vendor_id',
        'branch_id', 'department_id', 'expected_delivery_date', 'delivery_address',
        'subtotal', 'vat_percentage', 'vat_amount', 'freight_charges',
        'discount_amount', 'total_amount', 'payment_terms', 'payment_terms_days',
        'status', 'sent_at', 'sent_by', 'acknowledged_at',
        'terms_conditions', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'freight_charges' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    protected $appends = ['status_badge'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (!$po->po_number) {
                $po->po_number = static::generatePONumber();
            }
            if (!$po->po_date) {
                $po->po_date = now()->toDateString();
            }
        });

        static::updating(function ($po) {
            $po->recalculateTotals();
        });
    }

    public static function generatePONumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $prefix = "PO-{$year}-{$month}-";
        $last = static::where('po_number', 'like', $prefix . '%')
            ->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->po_number, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum(\DB::raw('quantity * unit_price'));
        $vat = round($subtotal * ($this->vat_percentage / 100), 2);
        $this->subtotal = $subtotal;
        $this->vat_amount = $vat;
        $this->total_amount = $subtotal + $vat + $this->freight_charges - $this->discount_amount;
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'draft'        => ['label' => 'Draft',        'severity' => 'secondary'],
            'sent'         => ['label' => 'Sent',         'severity' => 'info'],
            'acknowledged' => ['label' => 'Acknowledged', 'severity' => 'warning'],
            'partial'      => ['label' => 'Partial',      'severity' => 'warning'],
            'received'     => ['label' => 'Received',     'severity' => 'success'],
            'closed'       => ['label' => 'Closed',       'severity' => 'success'],
            'cancelled'    => ['label' => 'Cancelled',    'severity' => 'danger'],
            default        => ['label' => $this->status,  'severity' => 'info'],
        };
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function vendor(): BelongsTo        { return $this->belongsTo(Vendor::class); }
    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo    { return $this->belongsTo(Department::class); }
    public function requisition(): BelongsTo   { return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo       { return $this->belongsTo(User::class, 'updated_by'); }
    public function sender(): BelongsTo        { return $this->belongsTo(User::class, 'sent_by'); }
    public function items(): HasMany           { return $this->hasMany(PurchaseOrderItem::class); }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeDraft($q)      { return $q->where('status', 'draft'); }
    public function scopeSent($q)       { return $q->where('status', 'sent'); }
    public function scopePending($q)    { return $q->whereNotIn('status', ['received', 'closed', 'cancelled']); }
    public function scopeSearch($q, $s) {
        return $q->where(fn($qq) =>
            $qq->where('po_number', 'like', "%{$s}%")
               ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$s}%"))
        );
    }
}