<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
       'name',
        'email',
        'phone',
        'password',
        'tenant_id',
        'role',
        'branch_id',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'unread_notification_count',
        'role_label',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Notification Helpers
    |--------------------------------------------------------------------------
    */

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->unread();
    }

    public function getUnreadNotificationCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'director_admin'           => 'Director Admin',
            'rector'                   => 'Rector',
            'principal'                => 'Principal',
            'vice_principal'           => 'Vice-Principal',
            'managing_director'        => 'Managing Director',
            'deputy_managing_director' => 'Deputy Managing Director',
            'head_of_department'       => 'Head of The Department',
            'deputy_head'              => 'Deputy Head',
            'chief_coordinator'        => 'Chief Coordinator',
            'teacher'                  => 'Teacher',
            'manager'                  => 'Manager',
            'officer'                  => 'Officer',
            'staff'                    => 'Staff',
            default                    => ucfirst($this->role ?? 'staff'),
        };
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    public function isDirectorAdmin(): bool        { return $this->role === 'director_admin'; }
    public function isRector(): bool               { return $this->role === 'rector'; }
    public function isPrincipal(): bool            { return $this->role === 'principal'; }
    public function isVicePrincipal(): bool        { return $this->role === 'vice_principal'; }
    public function isManagingDirector(): bool     { return $this->role === 'managing_director'; }
    public function isDeputyManagingDirector(): bool { return $this->role === 'deputy_managing_director'; }
    public function isHeadOfDepartment(): bool     { return $this->role === 'head_of_department'; }
    public function isDeputyHead(): bool           { return $this->role === 'deputy_head'; }
    public function isChiefCoordinator(): bool     { return $this->role === 'chief_coordinator'; }
    public function isTeacher(): bool              { return $this->role === 'teacher'; }
    public function isManager(): bool              { return $this->role === 'manager'; }
    public function isOfficer(): bool              { return $this->role === 'officer'; }
    public function isStaff(): bool                { return $this->role === 'staff'; }

    /**
     * Check if user has management-level access
     * (can approve PRs, manage staff, etc.)
     */
    public function isManagement(): bool
    {
        return $this->hasRole([
            'director_admin',
            'rector',
            'principal',
            'vice_principal',
            'managing_director',
            'deputy_managing_director',
            'head_of_department',
            'deputy_head',
            'chief_coordinator',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfRole($query, string|array $roles)
    {
        return $query->whereIn('role', (array) $roles);
    }

    public function scopeOfBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOfDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }



    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

}