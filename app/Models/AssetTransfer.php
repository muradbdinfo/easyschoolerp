<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transfer_number', 'asset_id',
        'from_branch_id', 'from_location', 'from_custodian_id',
        'to_branch_id', 'to_location', 'to_custodian_id',
        'transfer_date', 'reason', 'condition_before', 'condition_after',
        'photos', 'notes', 'status',
        'approved_by', 'approved_at', 'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approved_at'   => 'datetime',
        'photos'        => 'array',
    ];

    protected $appends = ['status_badge'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($t) {
            if (empty($t->transfer_number)) {
                $last = self::withTrashed()->latest('id')->first();
                $num  = $last ? intval(substr($last->transfer_number, 3)) + 1 : 1;
                $t->transfer_number = 'TRF-' . str_pad($num, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function asset(): BelongsTo          { return $this->belongsTo(Asset::class); }
    public function fromBranch(): BelongsTo     { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo       { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function fromCustodian(): BelongsTo  { return $this->belongsTo(User::class, 'from_custodian_id'); }
    public function toCustodian(): BelongsTo    { return $this->belongsTo(User::class, 'to_custodian_id'); }
    public function approvedBy(): BelongsTo     { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'pending'   => ['label' => 'Pending',   'severity' => 'warning'],
            'approved'  => ['label' => 'Approved',  'severity' => 'success'],
            'completed' => ['label' => 'Completed', 'severity' => 'success'],
            'rejected'  => ['label' => 'Rejected',  'severity' => 'danger'],
            default     => ['label' => $this->status, 'severity' => 'info'],
        };
    }
}