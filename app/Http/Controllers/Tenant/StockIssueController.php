<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StockIssueRequest;
use App\Models\StockIssueItem;
use App\Models\Item;
use App\Models\Department;
use App\Models\Branch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockIssueController extends Controller
{
    public function __construct(protected StockService $stock) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = StockIssueRequest::with(['department:id,name','branch:id,name','requester:id,name']);

        // dept_head sees only their dept
        if ($user->role === 'dept_head') {
            $query->where('department_id', $user->department_id);
        } elseif ($user->role === 'staff') {
            $query->where('requested_by', $user->id);
        }

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('search')) {
            $query->where('sir_number', 'like', "%{$request->search}%")
                  ->orWhere('purpose',  'like', "%{$request->search}%");
        }

        return Inertia::render('Tenant/Stock/Index', [
            'sirs'        => $query->latest()->paginate(15)->withQueryString(),
            'departments' => Department::select('id','name')->get(),
            'filters'     => $request->only(['status','department_id','search']),
            'canIssue'    => in_array($user->role, ['admin','director_admin','po_staff','storekeeper']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Stock/Create', [
            'departments' => Department::select('id','name')->get(),
            'branches'    => Branch::select('id','name')->get(),
            'items'       => Item::where('current_stock', '>', 0)
                                ->select('id','name','code','unit','current_stock')
                                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id'    => 'required|exists:departments,id',
            'branch_id'        => 'required|exists:branches,id',
            'required_by_date' => 'nullable|date',
            'purpose'          => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.item_id'           => 'required|exists:items,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.unit'              => 'required|string',
        ]);

        DB::transaction(function () use ($data) {
            $sir = StockIssueRequest::create([
                'department_id'    => $data['department_id'],
                'branch_id'        => $data['branch_id'],
                'requested_by'     => auth()->id(),
                'request_date'     => now()->toDateString(),
                'required_by_date' => $data['required_by_date'] ?? null,
                'purpose'          => $data['purpose'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'status'           => 'submitted',
            ]);

            foreach ($data['items'] as $line) {
                StockIssueItem::create([
                    'stock_issue_request_id' => $sir->id,
                    'item_id'                => $line['item_id'],
                    'quantity_requested'     => $line['quantity_requested'],
                    'quantity_issued'        => 0,
                    'unit'                   => $line['unit'],
                    'notes'                  => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('tenant.stock-issues.index')
            ->with('success', 'Stock issue request submitted.');
    }

    public function show(StockIssueRequest $stockIssue)
    {
        $stockIssue->load(['department','branch','requester','issuer','items.item:id,name,code,unit,current_stock']);

        return Inertia::render('Tenant/Stock/Show', [
            'sir'      => $stockIssue,
            'canIssue' => in_array(auth()->user()->role,
                            ['admin','director_admin','po_staff','storekeeper'])
                          && in_array($stockIssue->status, ['submitted','partially_issued']),
        ]);
    }

    /**
     * Storekeeper issues stock — decrements item quantities.
     */
    public function issue(Request $request, StockIssueRequest $stockIssue)
    {
        $request->validate([
            'quantities'   => 'required|array',
            'quantities.*' => 'numeric|min:0',
        ]);

        $createdAssets = $this->stock->processIssue($stockIssue, $request->quantities);

        $msg = 'Stock issued successfully.';
        if (count($createdAssets)) {
            $tags = implode(', ', array_column($createdAssets, 'asset_tag'));
            $msg .= ' Assets created: ' . $tags;
        }

        return back()->with('success', $msg)->with('created_assets', $createdAssets);
    }

    public function cancel(StockIssueRequest $stockIssue)
    {
        abort_unless($stockIssue->status === 'submitted', 422, 'Cannot cancel this request.');
        $stockIssue->update(['status' => 'cancelled']);
        return back()->with('success', 'Request cancelled.');
    }

    /**
     * Stock ledger for an item.
     */
    public function ledger(Request $request)
    {
        $item = Item::findOrFail($request->item_id);
        $ledger = DB::table('stock_ledger')
            ->where('item_id', $item->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Tenant/Stock/Ledger', [
            'item'   => $item,
            'ledger' => $ledger,
        ]);
    }
}