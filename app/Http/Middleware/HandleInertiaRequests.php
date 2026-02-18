<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [

            // ── Auth user ─────────────────────────────────────────────────────
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'staff',
                ] : null,
            ],

            // ── School / tenant info (for TenantLayout sidebar) ───────────────
            'school' => $user?->tenant ? [
                'name'      => $user->tenant->name,
                'subdomain' => $user->tenant->subdomain,
                'logo'      => $user->tenant->logo,
            ] : null,

            // ── Branches (for branch selector in navbar) ───────────────────────
            'branches' => fn () => $user
                ? Branch::select('id', 'name')->orderBy('name')->get()
                : [],

            // ── Notifications (for bell in TenantLayout) ──────────────────────
            'unreadNotificationsCount' => fn () => $user
                ? $user->notifications()->unread()->count()
                : 0,

            'recentNotifications' => fn () => $user
                ? $user->notifications()
                      ->latest()
                      ->take(10)
                      ->get(['id', 'type', 'title', 'message', 'action_url', 'read_at', 'created_at'])
                : [],

            // ── Ziggy (named routes in JS) ────────────────────────────────────
            'ziggy' => fn () => array_merge(
                (new Ziggy)->toArray(),
                ['location' => $request->url()]
            ),

            // ── Flash messages ────────────────────────────────────────────────
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

        ]);
    }
}