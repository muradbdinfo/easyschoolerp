<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function index(): Response
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        return Inertia::render('Tenant/Settings/School/Index1', [
            'tenant'     => $tenant,
            'allModules' => self::availableModules(),
            'logoUrl'    => $tenant->logo
                                ? Storage::disk('public')->url($tenant->logo)
                                : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'primary_color' => 'nullable|string|max:20',
            'logo'          => 'nullable|image|max:2048',
        ]);

        $data = $request->only([
            'name', 'contact_name', 'contact_email',
            'contact_phone', 'primary_color',
        ]);

        // Merge settings sub-fields into existing settings JSON
        $incoming = array_filter([
            'address'             => $request->input('settings.address'),
            'city'                => $request->input('settings.city'),
            'country'             => $request->input('settings.country'),
            'phone'               => $request->input('settings.phone'),
            'website'             => $request->input('settings.website'),
            'academic_year_start' => $request->input('settings.academic_year_start'),
            'academic_year_end'   => $request->input('settings.academic_year_end'),
            'currency'            => $request->input('settings.currency'),
            'timezone'            => $request->input('settings.timezone'),
            'date_format'         => $request->input('settings.date_format'),
        ], fn ($v) => $v !== null);

        $data['settings'] = array_merge($tenant->settings ?? [], $incoming);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $data['logo'] = $request->file('logo')->store('tenants/logos', 'public');
        }

        $tenant->update($data);

        return back()->with('success', 'School settings saved successfully.');
    }

    public function removeLogo(): RedirectResponse
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
            $tenant->update(['logo' => null]);
        }

        return back()->with('success', 'Logo removed.');
    }

    public function updateModules(Request $request): RedirectResponse
    {
        $request->validate([
            'active_modules'   => 'required|array',
            'active_modules.*' => 'string|in:procurement,assets',
        ]);

        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $tenant->update(['active_modules' => $request->active_modules]);

        return back()->with('success', 'Modules updated successfully.');
    }

    public static function availableModules(): array
    {
        return [
            'procurement' => [
                'label'       => 'Procurement',
                'description' => 'Vendors, Items, Purchase Requisitions, Purchase Orders, Goods Receipt',
                'icon'        => 'ShoppingCart',
            ],
            'assets' => [
                'label'       => 'Asset Management',
                'description' => 'Asset Register, Transfers, Maintenance, Depreciation, Physical Verification',
                'icon'        => 'Box',
            ],
        ];
    }
}