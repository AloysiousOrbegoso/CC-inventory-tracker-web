<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\PurchaseHistory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ForecastingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id');
        $months = (int) $request->query('months', 6);

        $now = Carbon::now();

        // ── Historical Data (last 6 months) ────────────────────────────
        $historical = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $historical[] = $this->getMonthData($monthStart, $monthEnd, $branchId, $isManager, $user);
        }

        // ── Calculate Trends ───────────────────────────────────────────
        $revenueTrend = $this->calculateTrend(collect($historical)->pluck('revenue')->toArray());
        $expenseTrend = $this->calculateTrend(collect($historical)->pluck('expenses')->toArray());

        // ── Project Next 3 Months ──────────────────────────────────────
        $projections = [];
        for ($i = 1; $i <= 3; $i++) {
            $projectedMonth = $now->copy()->addMonths($i);
            $lastRevenue = end($historical)['revenue'];
            $lastExpenses = end($historical)['expenses'];

            $projectedRevenue = max(0, $lastRevenue + ($revenueTrend * $i));
            $projectedExpenses = max(0, $lastExpenses + ($expenseTrend * $i));

            $projections[] = [
                'month' => $projectedMonth->format('M Y'),
                'revenue' => round($projectedRevenue, 2),
                'expenses' => round($projectedExpenses, 2),
                'profit' => round($projectedRevenue - $projectedExpenses, 2),
                'confidence' => max(50, 95 - ($i * 15)), // Decreasing confidence
            ];
        }

        // ── Staffing Needs (based on transaction volume by day) ────────
        $avgDailyTransactions = Transaction::where('created_at', '>=', $now->copy()->subDays(30))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($isManager, fn($q) => $q->where('branch_id', $user->branch_id))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->avg('count') ?? 0;

        $staffingNeeds = match(true) {
            $avgDailyTransactions > 50 => 'High (6+ staff recommended)',
            $avgDailyTransactions > 25 => 'Medium (4-5 staff recommended)',
            $avgDailyTransactions > 10 => 'Standard (3-4 staff recommended)',
            default => 'Low (2-3 staff sufficient)',
        };

        // ── Inventory Needs (based on usage trends) ────────────────────
        $avgMonthlySupplies = PurchaseHistory::where('purchased_at', '>=', $now->copy()->subMonths(3))
            ->when($branchId, fn($q) => $q->whereHas('recorder', fn($q) => $q->where('branch_id', $branchId)))
            ->when($isManager, fn($q) => $q->whereHas('recorder', fn($q) => $q->where('branch_id', $user->branch_id)))
            ->sum(DB::raw('unit_price * quantity')) / 3;

        $projectedSupplyCost = $avgMonthlySupplies * 1.05; // 5% buffer

        // ── Branches ───────────────────────────────────────────────────
        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('forecasting.index', [
            'historical' => $historical,
            'projections' => $projections,
            'revenueTrend' => $revenueTrend,
            'expenseTrend' => $expenseTrend,
            'avgDailyTransactions' => round($avgDailyTransactions, 1),
            'staffingNeeds' => $staffingNeeds,
            'avgMonthlySupplies' => round($avgMonthlySupplies, 2),
            'projectedSupplyCost' => round($projectedSupplyCost, 2),
            'months' => $months,
            'branchId' => $branchId,
            'branches' => $branches,
        ]);
    }

    private function getMonthData(Carbon $start, Carbon $end, ?int $branchId, bool $isManager, $user): array
    {
        $revenue = Transaction::whereBetween('created_at', [$start, $end]);
        $expenses = Payment::whereBetween('paid_at', [$start, $end])->where('status', 'paid')->where('category', '!=', 'salary');
        $salary = Payslip::whereBetween('paid_at', [$start, $end])->where('status', 'paid');
        $supplies = PurchaseHistory::whereBetween('purchased_at', [$start, $end]);

        if ($branchId) {
            $revenue->where('branch_id', $branchId);
            $expenses->where('branch_id', $branchId);
            $salary->where('branch_id', $branchId);
            $supplies->whereHas('recorder', fn($q) => $q->where('branch_id', $branchId));
        } elseif ($isManager) {
            $revenue->where('branch_id', $user->branch_id);
            $expenses->where('branch_id', $user->branch_id);
            $salary->where('branch_id', $user->branch_id);
            $supplies->whereHas('recorder', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $totalRevenue = $revenue->sum('total_amount');
        $totalExpenses = $expenses->sum('amount') + $salary->sum('gross_pay') + $supplies->sum(DB::raw('unit_price * quantity'));

        return [
            'month' => $start->format('M Y'),
            'revenue' => (float) $totalRevenue,
            'expenses' => (float) $totalExpenses,
            'profit' => (float) ($totalRevenue - $totalExpenses),
        ];
    }

    private function calculateTrend(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        // Simple linear regression slope
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return 0;

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }
}
