<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'subdomain',
        'database_name',
        'status',
        'plan',
        'active_modules',
        'mrr',
        'trial_ends_at',
        'subscription_ends_at',
        'activated_at',
        'suspended_at',
        'contact_name',
        'contact_email',
        'contact_phone',
        'logo',
        'primary_color',
        'settings',
    ];

    protected $casts = [
        'active_modules' => 'array',
        'settings' => 'array',
        'trial_ends_at' => 'date',
        'subscription_ends_at' => 'date',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'mrr' => 'decimal:2',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->active_modules ?? []);
    }
}