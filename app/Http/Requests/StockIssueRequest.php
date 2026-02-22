<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIssueRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sir_number','department_id','branch_id','requested_by','issued_by',
        'request_date','required_by_date','issued_date',
        'status','purpose','notes',
    ];

    protected $appends = ['status_badge'];

    protected $casts = ['request_date'=>'date','required_by_date'=>'date','issued_date'=>'date'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($m) {
            if (!$m->sir_number) {
                $m->sir_number = 'SIR-' . date('Y-m') . '-' . str_pad(
                    (static::withTrashed()->whereYear('created_at', date('Y'))
                        ->whereMonth('created_at', date('m'))->count() + 1),
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }

    public function department(): BelongsTo  { return $this->belongsTo(Department::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function requester(): BelongsTo   { return $this->belongsTo(User::class, 'requested_by'); }
    public function issuer(): BelongsTo      { return $this->belongsTo(User::class, 'issued_by'); }
    public function items(): HasMany         { return $this->hasMany(StockIssueItem::class); }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'draft'            => ['label'=>'Draft',            'severity'=>'secondary'],
            'submitted'        => ['label'=>'Submitted',        'severity'=>'warning'],
            'partially_issued' => ['label'=>'Partially Issued', 'severity'=>'info'],
            'issued'           => ['label'=>'Issued',           'severity'=>'success'],
            'cancelled'        => ['label'=>'Cancelled',        'severity'=>'danger'],
            default            => ['label'=>$this->status,      'severity'=>'secondary'],
        };
    }
}