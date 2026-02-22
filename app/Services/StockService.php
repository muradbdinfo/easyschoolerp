<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Item;
use App\Models\StockIssueRequest;
use App\Models\StockIssueItem;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function increase(int $itemId, float $qty, string $type, ?int $refId, string $notes = ''): void
    {
        $this->move($itemId, $qty, 'in', $type, $refId, $notes);
    }

    public function decrease(int $itemId, float $qty, string $type, ?int $refId, string $notes = ''): void
    {
        $this->move($itemId, $qty, 'out', $type, $refId, $notes);
    }

    /**
     * Process issue: decrement stock + auto-create assets if item.is_asset = true
     * Returns array of created assets for display
     */
    public function processIssue(StockIssueRequest $sir, array $issuedQtys): array
    {
        $createdAssets = [];

        DB::transaction(function () use ($sir, $issuedQtys, &$createdAssets) {
            $sir->load(['items.item', 'department', 'branch']);
            $allFulfilled = true;

            foreach ($sir->items as $line) {
                $toIssue = (float) ($issuedQtys[$line->id] ?? 0);
                if ($toIssue <= 0) { $allFulfilled = false; continue; }

                $available = (float) Item::where('id', $line->item_id)->value('current_stock');
                $toIssue   = min($toIssue, $available);
                if ($toIssue <= 0) { $allFulfilled = false; continue; }

                $line->increment('quantity_issued', $toIssue);

                $this->decrease(
                    $line->item_id, $toIssue, 'issue', $sir->id,
                    "Issued to {$sir->department->name} — {$sir->sir_number}"
                );

                // Auto-create Asset records if item is_asset flag is true
                if ($line->item->is_asset) {
                    $assets = $this->createAssetsFromIssue($line, $sir, (int) $toIssue);
                    $createdAssets = array_merge($createdAssets, $assets);
                }

                if ((float) $line->fresh()->quantity_issued < (float) $line->quantity_requested) {
                    $allFulfilled = false;
                }
            }

            $sir->update([
                'status'      => $allFulfilled ? 'issued' : 'partially_issued',
                'issued_by'   => auth()->id(),
                'issued_date' => now()->toDateString(),
            ]);
        });

        return $createdAssets;
    }

    // ── Private: create one Asset per unit issued ─────────────────────────

    private function createAssetsFromIssue(StockIssueItem $line, StockIssueRequest $sir, int $qty): array
    {
        $item   = $line->item;
        $assets = [];

        for ($i = 0; $i < $qty; $i++) {
            $asset = Asset::create([
                'name'             => $item->name,
                'item_id'          => $item->id,
                'acquisition_date' => now()->toDateString(),
                'acquisition_cost' => $item->current_price ?? $item->last_purchase_price ?? 0,
                'branch_id'        => $sir->branch_id,
                'custodian_id'     => $sir->requested_by,
                'status'           => 'active',
                'condition'        => 'good',
                'notes'            => "Auto-created from Stock Issue {$sir->sir_number}",
                'created_by'       => auth()->id() ?? 1,
            ]);

            $assets[] = [
                'id'        => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'name'      => $asset->name,
            ];
        }

        return $assets;
    }

    // ── Core ledger ───────────────────────────────────────────────────────

    private function move(int $itemId, float $qty, string $direction,
                          string $type, ?int $refId, string $notes): void
    {
        DB::transaction(function () use ($itemId, $qty, $direction, $type, $refId, $notes) {
            $item   = Item::lockForUpdate()->findOrFail($itemId);
            $before = (float) $item->current_stock;
            $after  = $direction === 'in' ? $before + $qty : max(0, $before - $qty);

            $item->update(['current_stock' => $after]);

            DB::table('stock_ledger')->insert([
                'item_id'        => $itemId,
                'type'           => $type,
                'direction'      => $direction,
                'quantity'       => $qty,
                'stock_before'   => $before,
                'stock_after'    => $after,
                'reference_type' => $type,
                'reference_id'   => $refId,
                'notes'          => $notes,
                'created_by'     => auth()->id() ?? 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }
}