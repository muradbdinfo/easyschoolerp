<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Custom in-app notification model.
 *
 * TABLE: `notifications` (migration: 2026_02_16_085409_create_notifications_table)
 *
 * WHY NOT `pr_notifications`:
 *   - `notifications` was created first (Feb 16) and has better indexes
 *     (user_id+created_at composite index for feed queries)
 *   - `pr_notifications` (Feb 17) is an accidental duplicate with identical columns
 *   - Use the `notifications` table only. The `pr_notifications` migration should
 *     be rolled back and deleted. See migration fix file included in this patch.
 *
 * WHY NOT Laravel's built-in DB notification channel:
 *   - Laravel's 'database' channel needs: uuid PK, notifiable_type, notifiable_id,
 *     data (JSON) columns — our table has NONE of these.
 *   - Our table uses direct user_id FK + individual title/message/action_url columns.
 *   - All 3 Approval*Notification classes have had 'database' removed from via().
 *   - ApprovalService::createNotification() writes directly to this model instead.
 */
class Notification extends Model
{
    use HasFactory;

    // ✅ FIX: was 'pr_notifications' — must be 'notifications'
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'related_type',
        'related_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }
}