<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.custom', [
            'branches' => $branches,
            'reportData' => null,
            'reportType' => null,
        ]);
    }

    public function generate(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        $validated = $request->validate([
            'report_type' => 'required|in:revenue,expenses,inventory,staff,customers',
            'branch_id' => 'nullable|exists:branches,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $branchId = $validated['branch_id'] ?? null;

        $reportData = match($validated['report_type']) {
            'revenue' => $this->generateRevenueReport($startDate, $endDate, $branchId, $isManager, $user),
            'expenses' => $this->generateExpenseReport($startDate, $endDate, $branchId, $isManager, $user),
            'inventory' => $this->generateInventoryReport($branchId, $isManager, $user),
            'staff' => $this->generateStaffReport($branchId, $isManager, $user),
            'customers' => $this->generateCustomerReport($branchId, $isManager, $user),
        };

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.custom', [
            'branches' => $branches,
            'reportData' => $reportData,
            'reportType' => $validated['report_type'],
        ]);
    }

    private function generateRevenueReport($start, $end, $branchId, $isManager, $user)
    {
        $query = \App\Models\Transaction::whereBetween('created_at', [$start, $end]);
        if ($branchId) $query->where('branch_id', $branchId);
        elseif ($isManager) $query->where('branch_id', $user->branch_id);

        return [
            'title' => 'Revenue Report',
            'period' => "$start to $end",
            'summary' => [
                'total_revenue' => $query->sum('total_amount'),
                'total_transactions' => $query->count(),
                'avg_transaction' => $query->avg('total_amount'),
            ],
            'by_branch' => (clone $query)->select('branch_id', DB::raw('SUM(total_amount) as total'))
                ->groupBy('branch_id')->with('branch')->get(),
        ];
    }

    private function generateExpenseReport($start, $end, $branchId, $isManager, $user)
    {
        $query = \App\Models\Payment::whereBetween('paid_at', [$start, $end])->where('status', 'paid');
        if ($branchId) $query->where('branch_id', $branchId);
        elseif ($isManager) $query->where('branch_id', $user->branch_id);

        return [
            'title' => 'Expense Report',
            'period' => "$start to $end",
            'summary' => [
                'total_expenses' => $query->sum('amount'),
                'by_category' => (clone $query)->select('category', DB::raw('SUM(amount) as total'))
                    ->groupBy('category')->pluck('total', 'category'),
            ],
        ];
    }

    private function generateInventoryReport($branchId, $isManager, $user)
    {
        $query = \App\Models\BranchStock::with('ingredient', 'branch');
        if ($branchId) $query->where('branch_id', $branchId);
        elseif ($isManager) $query->where('branch_id', $user->branch_id);

        $stocks = $query->get();

        return [
            'title' => 'Inventory Report',
            'summary' => [
                'total_items' => $stocks->count(),
                'total_value' => $stocks->sum('current_quantity'),
                'low_stock' => $stocks->where('current_quantity', '<=', fn($s) => $s->min_threshold)->count(),
            ],
            'items' => $stocks,
        ];
    }

    private function generateStaffReport($branchId, $isManager, $user)
    {
        $query = \App\Models\User::where('role', '!=', 'super_admin');
        if ($branchId) $query->where('branch_id', $branchId);
        elseif ($isManager) $query->where('branch_id', $user->branch_id);

        $staff = $query->with('branch')->get();

        return [
            'title' => 'Staff Report',
            'summary' => [
                'total_staff' => $staff->count(),
                'managers' => $staff->where('role', 'manager')->count(),
                'staff' => $staff->where('role', 'staff')->count(),
            ],
            'staff' => $staff,
        ];
    }

    private function generateCustomerReport($branchId, $isManager, $user)
    {
        $query = \App\Models\Customer::with('branch');
        if ($branchId) $query->where('branch_id', $branchId);
        elseif ($isManager) $query->where('branch_id', $user->branch_id);

        $customers = $query->get();

        return [
            'title' => 'Customer Report',
            'summary' => [
                'total_customers' => $customers->count(),
                'total_spent' => $customers->sum('total_spent'),
                'avg_spent' => $customers->avg('total_spent'),
                'by_tier' => $customers->groupBy('tier')->map(fn($c) => $c->count()),
            ],
        ];
    }
}
