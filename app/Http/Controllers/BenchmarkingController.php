<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BenchmarkingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        if ($isManager) {
            return redirect()->route('dashboard')->with('error', 'Benchmarking is available to owners only.');
        }

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $period = $request->query('period', 'month');
        $months = (int) $request->query('months', 1);

        $now = \Illuminate\Support\Carbon::now();
        $startDate = $now->copy()->subMonths($months)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        $branchStats = $branches->map(function ($branch) use ($startDate, $endDate) {
            $revenue = Transaction::where('branch_id', $branch->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount');

            $expenses = Payment::where('branch_id', $branch->id)
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->where('category', '!=', 'salary')
                ->sum('amount');

            $salary = Payslip::where('branch_id', $branch->id)
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->sum('gross_pay');

            $staffCount = \App\Models\User::where('branch_id', $branch->id)
                ->where('role', '!=', 'super_admin')
                ->count();

            $totalExpenses = $expenses + $salary;
            $profit = $revenue - $totalExpenses;
            $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
            $revenuePerEmployee = $staffCount > 0 ? $revenue / $staffCount : 0;

            return [
                'name' => $branch->name,
                'revenue' => (float) $revenue,
                'expenses' => (float) $totalExpenses,
                'profit' => (float) $profit,
                'profit_margin' => round($profitMargin, 1),
                'staff_count' => $staffCount,
                'revenue_per_employee' => round($revenuePerEmployee, 2),
            ];
        });

        // Calculate company totals
        $companyTotals = [
            'revenue' => $branchStats->sum('revenue'),
            'expenses' => $branchStats->sum('expenses'),
            'profit' => $branchStats->sum('profit'),
            'profit_margin' => $branchStats->sum('revenue') > 0
                ? round(($branchStats->sum('profit') / $branchStats->sum('revenue')) * 100, 1)
                : 0,
        ];

        // Rankings
        $rankings = $branchStats->sortByDesc('profit_margin')->values();

        return view('benchmarking.index', [
            'branchStats' => $branchStats,
            'companyTotals' => $companyTotals,
            'rankings' => $rankings,
            'months' => $months,
        ]);
    }
}
