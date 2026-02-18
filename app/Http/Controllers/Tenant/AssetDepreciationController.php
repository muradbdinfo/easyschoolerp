<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetDepreciationSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetDepreciationController extends Controller
{
    public function index(Request $request): Response
    {
        // Show depreciation runs (grouped by year/month)
        $runs = AssetDepreciationSchedule::select('year', 'month')
            ->selectRaw('COUNT(DISTINCT asset_id) as assets_processed')
            ->selectRaw('SUM(depreciation_amount) as total_depreciation')
            ->selectRaw('MAX(processed_date) as processed_date')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(24);

        return Inertia::render('Tenant/Assets/Depreciation/Index', [
            'runs'    => $runs,
            'filters' => $request->only(['year']),
        ]);
    }

    public function run(): Response
    {
        // Preview assets to be deprecated for selected month
        $month = (int) request('month', now()->month);
        $year  = (int) request('year',  now()->year);

        // Check if already run
        $alreadyRun = AssetDepreciationSchedule::where('year', $year)
            ->where('month', $month)
            ->exists();

        // Get eligible assets
        $assets = Asset::with('category')
            ->where('status', 'active')
            ->where('depreciation_method', '!=', 'none')
            ->where('depreciation_rate', '>', 0)
            ->where('depreciation_start_date', '<=', now()->startOfMonth())
            ->get()
            ->map(function ($asset) {
                $monthly = $asset->calculateMonthlyDepreciation();
                return [
                    'id'                  => $asset->id,
                    'asset_tag'           => $asset->asset_tag,
                    'name'                => $asset->name,
                    'category'            => $asset->category?->name,
                    'depreciation_method' => strtoupper($asset->depreciation_method),
                    'opening_value'       => $asset->net_book_value,
                    'depreciation_amount' => round($monthly, 2),
                    'closing_value'       => round(max(0, $asset->net_book_value - $monthly), 2),
                ];
            });

        return Inertia::render('Tenant/Assets/Depreciation/Run', [
            'assets'     => $assets,
            'month'      => $month,
            'year'       => $year,
            'alreadyRun' => $alreadyRun,
            'summary'    => [
                'total_assets'       => $assets->count(),
                'total_depreciation' => $assets->sum('depreciation_amount'),
            ],
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2100',
        ]);

        $month = $request->month;
        $year  = $request->year;

        // Prevent double-processing
        if (AssetDepreciationSchedule::where('year', $year)->where('month', $month)->exists()) {
            return back()->with('error', "Depreciation for {$month}/{$year} already processed.");
        }

        $assets = Asset::where('status', 'active')
            ->where('depreciation_method', '!=', 'none')
            ->where('depreciation_rate', '>', 0)
            ->where('depreciation_start_date', '<=', now()->startOfMonth())
            ->get();

        $processed = 0;
        foreach ($assets as $asset) {
            $monthly  = $asset->calculateMonthlyDepreciation();
            $opening  = $asset->net_book_value;
            $closing  = max(0, $opening - $monthly);

            AssetDepreciationSchedule::create([
                'asset_id'            => $asset->id,
                'year'                => $year,
                'month'               => $month,
                'opening_value'       => $opening,
                'depreciation_amount' => $monthly,
                'closing_value'       => $closing,
                'processed_date'      => now(),
                'processed_by'        => auth()->id(),
            ]);

            $asset->update([
                'accumulated_depreciation' => $asset->accumulated_depreciation + $monthly,
                'net_book_value'           => $closing,
            ]);

            $processed++;
        }

        return redirect()->route('tenant.assets.depreciation.index')
            ->with('success', "Depreciation processed for {$processed} assets.");
    }

    public function schedule(Asset $asset): Response
    {
        $asset->load(['category', 'depreciationSchedules']);

        return Inertia::render('Tenant/Assets/Depreciation/Schedule', [
            'asset'    => $asset,
            'schedule' => $asset->depreciationSchedules,
        ]);
    }
}