<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCycle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'cycle_year', 'start_date', 'end_date',
        'scope', 'scope_ids', 'total_assets', 'verified_count',
        'discrepancy_count', 'status', 'team_members', 'created_by',
        'completed_at', 'certificate_path',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'completed_at'  => 'datetime',
        'scope_ids'     => 'array',
        'team_members'  => 'array',
    ];

    protected $appends = ['progress_percent', 'status_badge'];

    public function items(): HasMany
    {
        return $this->hasMany(VerificationItem::class, 'cycle_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_assets === 0) return 0;
        return round(($this->verified_count / $this->total_assets) * 100, 1);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'planning'    => ['label' => 'Planning',     'severity' => 'info'],
            'in_progress' => ['label' => 'In Progress',  'severity' => 'warning'],
            'completed'   => ['label' => 'Completed',    'severity' => 'success'],
            'cancelled'   => ['label' => 'Cancelled',    'severity' => 'secondary'],
            default       => ['label' => $this->status,  'severity' => 'info'],
        };
    }
}