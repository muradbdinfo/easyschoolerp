<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pr_number',
        'pr_date',
        'user_id',
        'department_id',
        'branch_id',
        'required_by_date',
        'priority',
        'purpose',
        'justification',
        'total_amount',
        'estimated_amount',
        'status',
        'current_approval_level',
        'required_approval_levels',
        'approval_history',
        'level_1_approver_id',
        'level_1_approved_at',
        'level_1_comments',
        'level_1_status',
        'level_2_approver_id',
        'level_2_approved_at',
        'level_2_comments',
        'level_2_status',
        'level_3_approver_id',
        'level_3_approved_at',
        'level_3_comments',
        'level_3_status',
        'final_approved_at',
        'final_approved_by',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'purchase_order_id',
        'attachments',
        'is_urgent',
        'notes',
    ];

    protected $casts = [
        'pr_date' => 'date',
        'required_by_date' => 'date',
        'total_amount' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'approval_history' => 'array',
        'attachments' => 'array',
        'is_urgent' => 'boolean',
        'level_1_approved_at' => 'datetime',
        'level_2_approved_at' => 'datetime',
        'level_3_approved_at' => 'datetime',
        'final_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pr) {
            if (!$pr->pr_number) {
                $pr->pr_number = static::generatePRNumber();
            }
            if (!$pr->pr_date) {
                $pr->pr_date = now();
            }
        });

        static::saving(function ($pr) {
            // Auto-calculate total from items
            $pr->calculateTotal();
        });
    }

    /**
     * Generate unique PR number
     */
    public static function generatePRNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Format: PR-YYYY-MM-0001
        $prefix = "PR-{$year}-{$month}-";
        
        // Get last PR number for this month
        $lastPR = static::where('pr_number', 'like', $prefix . '%')
                       ->orderBy('pr_number', 'desc')
                       ->first();
        
        if ($lastPR) {
            $lastNumber = (int) substr($lastPR->pr_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('estimated_total');
        $this->estimated_amount = $this->total_amount;
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function level1Approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_1_approver_id');
    }

    public function level2Approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_2_approver_id');
    }

    public function level3Approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_3_approver_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            'submitted',
            'pending_level_1',
            'pending_level_2',
            'pending_level_3'
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeMyRequisitions($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePendingMyApproval($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('level_1_approver_id', $userId)
              ->where('level_1_status', 'pending')
              ->orWhere(function ($q2) use ($userId) {
                  $q2->where('level_2_approver_id', $userId)
                     ->where('level_2_status', 'pending');
              })
              ->orWhere(function ($q3) use ($userId) {
                  $q3->where('level_3_approver_id', $userId)
                     ->where('level_3_status', 'pending');
              });
        });
    }

    /**
     * Accessors
     */
    public function getIsEditableAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'draft' => ['label' => 'Draft', 'severity' => 'secondary'],
            'submitted' => ['label' => 'Submitted', 'severity' => 'info'],
            'pending_level_1' => ['label' => 'Pending Dept Head', 'severity' => 'warning'],
            'pending_level_2' => ['label' => 'Pending VP/Principal', 'severity' => 'warning'],
            'pending_level_3' => ['label' => 'Pending Board', 'severity' => 'warning'],
            'approved' => ['label' => 'Approved', 'severity' => 'success'],
            'rejected' => ['label' => 'Rejected', 'severity' => 'danger'],
            'cancelled' => ['label' => 'Cancelled', 'severity' => 'secondary'],
            'closed' => ['label' => 'Closed', 'severity' => 'secondary'],
        ];

        return $badges[$this->status] ?? ['label' => $this->status, 'severity' => 'info'];
    }

    public function getPriorityBadgeAttribute(): array
    {
        $badges = [
            'low' => ['label' => 'Low', 'severity' => 'info'],
            'medium' => ['label' => 'Medium', 'severity' => 'warning'],
            'high' => ['label' => 'High', 'severity' => 'danger'],
            'urgent' => ['label' => 'Urgent', 'severity' => 'danger'],
        ];

        return $badges[$this->priority] ?? ['label' => $this->priority, 'severity' => 'info'];
    }
}