<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationItem extends Model
{
    protected $fillable = [
        'cycle_id', 'asset_id', 'is_present', 'location_correct',
        'custodian_correct', 'condition', 'actual_location', 'actual_custodian',
        'discrepancy_details', 'severity', 'resolution_status', 'resolution_notes',
        'photos', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'is_present'       => 'boolean',
        'location_correct' => 'boolean',
        'custodian_correct'=> 'boolean',
        'photos'           => 'array',
        'verified_at'      => 'datetime',
    ];

    protected $appends = ['has_discrepancy', 'severity_badge'];

    public function cycle(): BelongsTo  { return $this->belongsTo(VerificationCycle::class, 'cycle_id'); }
    public function asset(): BelongsTo  { return $this->belongsTo(Asset::class, 'asset_id'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function getHasDiscrepancyAttribute(): bool
    {
        return $this->verified_at && (
            !$this->is_present ||
            !$this->location_correct ||
            !$this->custodian_correct ||
            in_array($this->condition, ['fair', 'poor'])
        );
    }

    public function getSeverityBadgeAttribute(): array
    {
        return match ($this->severity) {
            'high'   => ['label' => 'High',   'severity' => 'danger'],
            'medium' => ['label' => 'Medium',  'severity' => 'warning'],
            'low'    => ['label' => 'Low',     'severity' => 'info'],
            default  => ['label' => '-',       'severity' => 'secondary'],
        };
    }
}