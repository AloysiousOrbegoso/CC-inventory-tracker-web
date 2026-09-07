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

class ProfitLossController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        // Period selection: month, quarter, year, custom
        $period = $request->query('period', 'month');
        $branchId = $request->query('branch_id');

        // Date range
        $now = Carbon::now();
        match ($period) {
            'month' => [
                $startDate = $now->copy()->startOfMonth(),
                $endDate = $now->copy()->endOfMonth(),
                $label = $now->format('F Y'),
            ],
            'quarter' => [
                $startDate = $now->copy()->startOfQuarter(),
                $endDate = $now->copy()->endOfQuarter(),
                $label = 'Q' . $now->quarter . ' ' . $now->year,
            ],
            'year' => [
                $startDate = $now->copy()->startOfYear(),
                $endDate = $now->copy()->endOfYear(),
                $label = (string) $now->year,
            ],
            'all' => [
                $startDate = Carbon::parse('2020-01-01'),
                $endDate = $now->copy()->endOfDay(),
                $label = 'All Time',
            ],
            default => [
                $startDate = $now->copy()->startOfMonth(),
                $endDate = $now->copy()->endOfMonth(),
                $label = $now->format('F Y'),
            ],
        };

        // ── Revenue (from transactions) ────────────────────────────────
        $revenueQuery = Transaction::whereBetween('created_at', [$startDate, $endDate]);
        if ($branchId) {
            $revenueQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $revenueQuery->where('branch_id', $user->branch_id);
        }
        $totalRevenue = (clone $revenueQuery)->sum('total_amount');

        // ── Expenses (from payments — exclude salary, handled via payslips) ──
        $expenseQuery = Payment::whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'paid');
        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $expenseQuery->where('branch_id', $user->branch_id);
        }

        // Category breakdown (excluding salary)
        $expenseByCategory = (clone $expenseQuery)
            ->where('category', '!=', 'salary')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $totalOverhead = array_sum($expenseByCategory);

        // ── Salary (from payslips) ─────────────────────────────────────
        $salaryQuery = Payslip::where('paid_at', '>=', $startDate)
            ->where('paid_at', '<=', $endDate)
            ->where('status', 'paid');
        if ($branchId) {
            $salaryQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $salaryQuery->where('branch_id', $user->branch_id);
        }
        $totalSalary = (clone $salaryQuery)->sum('gross_pay');
        $totalDeductions = (clone $salaryQuery)->sum('deductions');

        // ── Supply Costs (from purchase_history) ────────────────────────
        $supplyQuery = PurchaseHistory::whereBetween('purchased_at', [$startDate, $endDate]);
        if ($branchId) {
            // purchase_history doesn't have branch_id; use recorded_by's branch
            $supplyQuery->whereHas('recorder', fn ($q) => $q->where('branch_id', $branchId));
        } elseif ($isManager) {
            $supplyQuery->whereHas('recorder', fn ($q) => $q->where('branch_id', $user->branch_id));
        }
        $totalSupplies = (clone $supplyQuery)->sum(DB::raw('unit_price * quantity'));

        // ── Totals ──────────────────────────────────────────────────────
        $totalExpenses = $totalOverhead + $totalSalary + $totalSupplies;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // ── Monthly Trend (last 6 months) ──────────────────────────────
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $monthRevenue = Transaction::whereBetween('created_at', [$monthStart, $monthEnd]);
            $monthExpenses = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->where('status', 'paid')->where('category', '!=', 'salary');
            $monthSalary = Payslip::whereBetween('paid_at', [$monthStart, $monthEnd])->where('status', 'paid');
            $monthSupplies = PurchaseHistory::whereBetween('purchased_at', [$monthStart, $monthEnd]);

            if ($branchId) {
                $monthRevenue->where('branch_id', $branchId);
                $monthExpenses->where('branch_id', $branchId);
                $monthSalary->where('branch_id', $branchId);
                $monthSupplies->whereHas('recorder', fn ($q) => $q->where('branch_id', $branchId));
            } elseif ($isManager) {
                $monthRevenue->where('branch_id', $user->branch_id);
                $monthExpenses->where('branch_id', $user->branch_id);
                $monthSalary->where('branch_id', $user->branch_id);
                $monthSupplies->whereHas('recorder', fn ($q) => $q->where('branch_id', $user->branch_id));
            }

            $mRevenue = $monthRevenue->sum('total_amount');
            $mExpenses = $monthExpenses->sum('amount') + $monthSalary->sum('gross_pay') + $monthSupplies->sum(DB::raw('unit_price * quantity'));

            $monthlyTrend[] = [
                'month' => $monthStart->format('M Y'),
                'revenue' => (float) $mRevenue,
                'expenses' => (float) $mExpenses,
                'profit' => (float) ($mRevenue - $mExpenses),
            ];
        }

        // ── Branches (for owner filter) ────────────────────────────────
        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('profit-loss.index', [
            'isManager' => $isManager,
            'period' => $period,
            'periodLabel' => $label,
            'branchId' => $branchId,
            'branches' => $branches,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'profitMargin' => $profitMargin,
            'totalOverhead' => $totalOverhead,
            'totalSalary' => $totalSalary,
            'totalDeductions' => $totalDeductions,
            'totalSupplies' => $totalSupplies,
            'expenseByCategory' => $expenseByCategory,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }
}
