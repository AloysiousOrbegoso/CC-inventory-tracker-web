<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $status = $request->query('status');
        $branchId = $request->query('branch_id');

        $query = PurchaseOrder::with(['branch', 'supplier', 'creator', 'items.ingredient']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        $purchaseOrders = $query->latest()->paginate(20);

        // Stats
        $statsQuery = PurchaseOrder::query();
        if ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'delivered' => (clone $statsQuery)->where('status', 'delivered')->count(),
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'stats' => $stats,
            'branchId' => $branchId,
            'status' => $status,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers create purchase orders.']);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($user->isManager() && $user->branch_id !== (int) $validated['branch_id']) {
            return back()->withErrors(['branch_id' => 'You can only create POs for your own branch.']);
        }

        $po = DB::transaction(function () use ($validated, $user) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'created_by' => $user->id,
                'status' => 'draft',
                'expected_delivery' => $validated['expected_delivery'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $po->items()->create([
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            $po->recalculateTotal();
            return $po;
        });

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', 'Purchase Order ' . $po->po_number . ' created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['branch', 'supplier', 'creator', 'items.ingredient']);

        return view('purchase-orders.show', [
            'po' => $purchaseOrder,
        ]);
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers update purchase orders.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,ordered,delivered,received,cancelled',
        ]);

        // Authorization check
        if ($user->isManager() && $user->branch_id !== $purchaseOrder->branch_id) {
            return back()->withErrors(['status' => 'Forbidden.']);
        }

        $newStatus = $validated['status'];
        $validTransitions = [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['approved', 'cancelled'],
            'approved' => ['ordered', 'cancelled'],
            'ordered' => ['delivered'],
            'delivered' => ['received'],
            'received' => [],
            'cancelled' => ['draft'],
        ];

        if (! in_array($newStatus, $validTransitions[$purchaseOrder->status] ?? [])) {
            return back()->withErrors(['status' => 'Cannot transition from ' . $purchaseOrder->status . ' to ' . $newStatus . '.']);
        }

        $data = ['status' => $newStatus];
        if ($newStatus === 'delivered') {
            $data['delivered_at'] = now();
        }

        $purchaseOrder->update($data);

        // When received, create stock restock movements
        if ($newStatus === 'received') {
            foreach ($purchaseOrder->items as $item) {
                $branchStock = \App\Models\BranchStock::firstOrCreate(
                    [
                        'branch_id' => $purchaseOrder->branch_id,
                        'ingredient_id' => $item->ingredient_id,
                    ],
                    ['current_quantity' => 0, 'min_threshold' => 0]
                );

                $branchStock->update([
                    'current_quantity' => $branchStock->current_quantity + $item->quantity,
                ]);

                \App\Models\StockMovement::create([
                    'branch_stock_id' => $branchStock->id,
                    'user_id' => $user->id,
                    'type' => 'restock',
                    'quantity_change' => $item->quantity,
                    'notes' => 'PO ' . $purchaseOrder->po_number . ' received',
                ]);

                // Also record in purchase_history
                \App\Models\PurchaseHistory::create([
                    'ingredient_id' => $item->ingredient_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'purchased_at' => now(),
                    'notes' => 'PO ' . $purchaseOrder->po_number,
                    'recorded_by' => $user->id,
                ]);
            }
        }

        return back()->with('success', 'Purchase Order status updated to ' . ucfirst($newStatus) . '.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $user = request()->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers delete purchase orders.']);
        }

        if ($user->isManager() && $user->branch_id !== $purchaseOrder->branch_id) {
            return back()->withErrors(['error' => 'Forbidden.']);
        }

        if (! in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return back()->withErrors(['error' => 'Only draft or pending POs can be deleted.']);
        }

        $poNumber = $purchaseOrder->po_number;
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase Order ' . $poNumber . ' deleted.');
    }
}
