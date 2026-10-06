<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\DiscrepancyAlert;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ShiftLog;
use App\Models\ShiftStockCount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DiscrepancyValueCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DiscrepancyValueCalculator $valueCalculator)
    {
    }

    public function index(): View
    {
        $user = auth()->user();
        $isManager = $user->isManager();
        $branchId = $isManager ? $user->branch_id : null;

        $expectedTotal = (float) ShiftStockCount::when($isManager, fn ($q) => $q->whereHas(
            'shiftLog', fn ($q2) => $q2->where('branch_id', $branchId)
        ))->sum('closing_quantity_expected');

        // ── Month-over-month comparisons ────────────────────────────────
        // The design labels most figures "vs last month". Nothing is stored
        // per-period, so each pair is recomputed from its source table.
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        $revenueFor = fn ($from, $to) => (float) Transaction::whereBetween('created_at', [$from, $to])
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        $revenueThis = $revenueFor($thisMonth, now());
        $revenueLast = $revenueFor($lastMonth, $thisMonth);

        $alertsFor = fn ($from, $to) => DiscrepancyAlert::whereBetween('created_at', [$from, $to])
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $alertsThis = $alertsFor($thisMonth, now());
        $alertsLast = $alertsFor($lastMonth, $thisMonth);

        $leakFor = fn ($from, $to) => (float) ShiftStockCount::where('variance', '<', 0)
            ->whereHas('shiftLog', fn ($q) => $q->whereBetween('shift_start', [$from, $to])
                ->when($isManager, fn ($q2) => $q2->where('branch_id', $branchId)))
            ->sum(DB::raw('ABS(variance)'));

        $leakThis = $leakFor($thisMonth, now());
        $leakLast = $leakFor($lastMonth, $thisMonth);

        // MONTH() is MySQL-only; SQLite (the test suite) needs strftime instead.
        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $monthlyTotals = Transaction::selectRaw("{$monthExpr} as m, SUM(total_amount) as total")
            ->whereYear('created_at', now()->year)
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('m')
            ->pluck('total', 'm');

        // ── Workforce ───────────────────────────────────────────────────
        // "Employees" are the staff and managers; the owner is excluded.
        $staffRoles = [User::ROLE_STAFF, User::ROLE_MANAGER];
        $employees  = User::whereIn('role', $staffRoles)
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId));

        $employeesTotal = (clone $employees)->count();
        $employeesNew   = (clone $employees)->where('created_at', '>=', $thisMonth)->count();
        $employeesLast  = (clone $employees)->whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        $onShift        = ShiftLog::where('status', 'open')
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->distinct('user_id')->count('user_id');

        // Who's actually on shift right now, per branch — the Employee
        // Status chart reads this as one bar per branch. Every branch in
        // scope appears even at zero, so the axis doesn't shrink to just
        // whichever branches happen to have someone clocked in.
        $onShiftByBranch = ShiftLog::where('status', 'open')
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('branch_id, COUNT(DISTINCT user_id) as c')
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id');

        $employeesByBranch = Branch::when($isManager, fn ($q) => $q->where('id', $branchId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->name => (int) ($onShiftByBranch[$b->id] ?? 0)]);

        // ── Branch Health Summary ──────────────────────────────────────
        // A branch shows a warning if it has any pending discrepancy alert
        // — the simplest defensible rule, not an invented percentage.
        $alertsByBranch = DiscrepancyAlert::where('status', 'pending')
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('branch_id, COUNT(*) as c')
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id');

        $lowStockByBranch = BranchStock::where('current_quantity', '<=', DB::raw('min_threshold'))
            ->where('min_threshold', '>', 0)
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('branch_id, COUNT(*) as c')
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id');

        $branchHealth = Branch::when($isManager, fn ($q) => $q->where('id', $branchId))
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'name' => $b->name,
                'pending_alerts' => (int) ($alertsByBranch[$b->id] ?? 0),
                'low_stock_count' => (int) ($lowStockByBranch[$b->id] ?? 0),
                'is_warning' => (int) ($alertsByBranch[$b->id] ?? 0) > 0,
            ]);

        // ── First-run setup nudge ─────────────────────────────────────
        // The dashboard is useless (and dishonest) without a catalog, so
        // point brand-new owners at the wizard until the pipeline exists.
        // Respects the skip flag; disappears forever once all three exist.
        $setupNeeded = null;
        if (AppSetting::get('setup_skipped_at') === null) {
            $hasIngredients = Ingredient::exists();
            $hasProducts = Product::exists();
            $hasStock = BranchStock::exists();

            if (! ($hasIngredients && $hasProducts && $hasStock)) {
                $setupNeeded = [
                    'has_ingredients' => $hasIngredients,
                    'has_products' => $hasProducts,
                    'has_stock' => $hasStock,
                ];
            }
        }

        return view('dashboard', [
            'branch_health' => $branchHealth,
            'setup_needed' => $setupNeeded,
            // Workforce headline. Resignations and employment type (full/part
            // time) are not in the schema, so the third tile uses role — the
            // workforce split we can actually report.
            'employees_total' => $employeesTotal,
            'employees_new' => $employeesNew,
            'employees_on_shift' => $onShift,
            'delta_employees' => $this->delta($employeesNew, $employeesLast),
            'employees_by_branch' => $employeesByBranch,
            // Each delta is [percent change, has_baseline]. Without a prior
            // period there is no honest percentage, so the view shows nothing.
            'delta_revenue' => $this->delta($revenueThis, $revenueLast),
            'delta_alerts' => $this->delta($alertsThis, $alertsLast),
            'delta_leakage' => $this->delta($leakThis, $leakLast),
            'revenue_this_month' => $revenueThis,

            'total_branches' => Branch::count(),
            'pending_alerts' => DiscrepancyAlert::where('status', 'pending')
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->count(),
            'low_stock_count' => BranchStock::where('current_quantity', '<=', DB::raw('min_threshold'))
                ->where('min_threshold', '>', 0)
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->count(),
            'total_sales' => Transaction::whereDate('created_at', today())
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->sum('total_amount'),
            'daily_sales' => Transaction::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'branches_with_sales' => Branch::with(['transactions' => fn ($q) => $q->whereDate('created_at', today())])
                ->when($isManager, fn ($q) => $q->where('id', $branchId))
                ->get()
                ->map(fn ($b) => [
                    'name' => $b->name,
                    'today_sales' => $b->transactions->sum('total_amount'),
                    'has_sales' => $b->transactions->count() > 0,
                ]),
            'recent_transactions' => Transaction::with('product', 'branch', 'user')
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->latest()->take(10)->get(),

            // Revenue for each month of the current year as exactly twelve
            // values, so the chart always plots a full Jan–Dec axis. Built by
            // mapping over the months rather than merging into a zero-filled
            // collection, since merge() renumbers integer keys.
            'monthly_revenue' => collect(range(1, 12))
                ->map(fn ($m) => (float) ($monthlyTotals[$m] ?? 0))
                ->values(),

            'annual_revenue' => Transaction::whereYear('created_at', now()->year)
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->sum('total_amount'),
            'leakage_pct' => $expectedTotal > 0
                ? (float) ShiftStockCount::where('variance', '<', 0)
                    ->when($isManager, fn ($q) => $q->whereHas('shiftLog', fn ($q2) => $q2->where('branch_id', $branchId)))
                    ->sum(DB::raw('ABS(variance)')) / $expectedTotal * 100
                : 0,
            'value_saved' => $this->valueCalculator->estimatedValueSaved($isManager, $branchId),

            'flag_counts' => DiscrepancyAlert::where('status', 'pending')
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->selectRaw('severity, COUNT(*) as c')
                ->groupBy('severity')
                ->pluck('c', 'severity'),
            'recent_flags' => DiscrepancyAlert::with('branch', 'ingredient', 'shiftLog.user')
                ->where('status', 'pending')
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->latest()
                ->take(6)
                ->get(),

            'top_earners' => Branch::withSum('transactions as revenue', 'total_amount')
                ->when($isManager, fn ($q) => $q->where('id', $branchId))
                ->orderByDesc('revenue')
                ->take(5)
                ->get(),
            'least_leakage' => Branch::when($isManager, fn ($q) => $q->where('id', $branchId))
                ->get()
                ->map(fn ($b) => [
                    'name' => $b->name,
                    'leak' => (float) ShiftStockCount::whereHas('shiftLog', fn ($q) => $q->where('branch_id', $b->id))
                        ->where('variance', '<', 0)
                        ->sum(DB::raw('ABS(variance)')),
                ])->sortBy('leak')->values(),

            'ongoing_shifts' => ShiftLog::with('branch', 'user')
                ->where('status', 'open')
                ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
                ->latest('shift_start')
                ->take(4)
                ->get(),
        ]);
    }

    /**
     * Percentage change between two periods.
     *
     * Returns null when the prior period has no baseline to divide by — the
     * dashboard labels these "vs last month", and a percentage against zero
     * would be presented as fact while meaning nothing.
     *
     * @return array{pct: float, direction: string}|null
     */
    private function delta(float $current, float $previous): ?array
    {
        if ($previous <= 0.0) {
            return null;
        }

        $pct = (($current - $previous) / $previous) * 100;

        return [
            'pct' => round(abs($pct), 1),
            'direction' => $pct >= 0 ? 'up' : 'down',
        ];
    }
}
