<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'maintenance_number', 'asset_id', 'type', 'frequency',
        'scheduled_date', 'completed_date', 'vendor_id',
        'estimated_cost', 'actual_cost', 'invoice_number',
        'description', 'work_performed', 'parts_replaced',
        'status', 'condition_after', 'next_due_date',
        'photos', 'notes', 'assigned_to', 'created_by',
    ];

    protected $casts = [
        'scheduled_date'  => 'date',
        'completed_date'  => 'date',
        'next_due_date'   => 'date',
        'estimated_cost'  => 'decimal:2',
        'actual_cost'     => 'decimal:2',
        'photos'          => 'array',
    ];

    protected $appends = ['status_badge', 'type_label'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->maintenance_number)) {
                $last = self::withTrashed()->latest('id')->first();
                $num  = $last ? intval(substr($last->maintenance_number, 4)) + 1 : 1;
                $m->maintenance_number = 'MNT-' . str_pad($num, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function asset(): BelongsTo    { return $this->belongsTo(Asset::class); }
    public function vendor(): BelongsTo   { return $this->belongsTo(Vendor::class); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'scheduled'   => ['label' => 'Scheduled',   'severity' => 'info'],
            'in_progress' => ['label' => 'In Progress',  'severity' => 'warning'],
            'completed'   => ['label' => 'Completed',    'severity' => 'success'],
            'cancelled'   => ['label' => 'Cancelled',    'severity' => 'secondary'],
            default       => ['label' => $this->status,  'severity' => 'info'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'routine'     => 'Routine Maintenance',
            'repair'      => 'Repair',
            'servicing'   => 'Servicing',
            'calibration' => 'Calibration',
            'upgrade'     => 'Upgrade',
            default       => ucfirst($this->type),
        };
    }

    public function scopeDueSoon($q, int $days = 7)
    {
        return $q->where('status', 'scheduled')
                 ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($q)
    {
        return $q->where('status', 'scheduled')->where('scheduled_date', '<', now());
    }
}