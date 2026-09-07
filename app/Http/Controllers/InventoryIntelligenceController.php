<?php

namespace App\Http\Controllers;

use App\Models\BranchStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryIntelligenceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id');

        // ── Expiring Items (within 7 days) ──────────────────────────────
        $expiringQuery = BranchStock::with('ingredient', 'branch')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now()->addDays(7))
            ->where('current_quantity', '>', 0);

        if ($branchId) {
            $expiringQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $expiringQuery->where('branch_id', $user->branch_id);
        }

        $expiringItems = $expiringQuery->orderBy('expires_at')->get()->map(function ($stock) {
            $daysUntilExpiry = Carbon::now()->diffInDays(Carbon::parse($stock->expires_at), false);
            return [
                'id' => $stock->id,
                'ingredient' => $stock->ingredient?->name ?? 'Unknown',
                'branch' => $stock->branch?->name ?? 'Unknown',
                'quantity' => $stock->current_quantity,
                'unit' => $stock->ingredient?->unit ?? '',
                'expires_at' => $stock->expires_at,
                'days_until' => max(0, (int) $daysUntilExpiry),
                'status' => $daysUntilExpiry <= 0 ? 'expired' : ($daysUntilExpiry <= 3 ? 'critical' : 'warning'),
            ];
        });

        // ── Low Stock / Reorder Alerts ──────────────────────────────────
        $lowStockQuery = BranchStock::with('ingredient', 'branch')
            ->where('current_quantity', '<=', DB::raw('min_threshold'))
            ->where('current_quantity', '>', 0);

        if ($branchId) {
            $lowStockQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $lowStockQuery->where('branch_id', $user->branch_id);
        }

        $lowStockItems = $lowStockQuery->get()->map(function ($stock) {
            // Calculate average daily usage from last 30 days
            $dailyUsage = StockMovement::where('branch_stock_id', $stock->id)
                ->where('type', StockMovement::TYPE_SALE)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->sum(DB::raw('ABS(quantity_change)')) / 30;

            $daysUntilEmpty = $dailyUsage > 0 ? floor($stock->current_quantity / $dailyUsage) : 999;

            return [
                'id' => $stock->id,
                'ingredient' => $stock->ingredient?->name ?? 'Unknown',
                'branch' => $stock->branch?->name ?? 'Unknown',
                'current' => $stock->current_quantity,
                'min_threshold' => $stock->min_threshold,
                'unit' => $stock->ingredient?->unit ?? '',
                'daily_usage' => round($dailyUsage, 2),
                'days_until_empty' => $daysUntilEmpty,
                'status' => $days_until_empty <= 3 ? 'critical' : ($days_until_empty <= 7 ? 'warning' : 'normal'),
            ];
        })->sortBy('days_until_empty')->values();

        // ── Out of Stock ────────────────────────────────────────────────
        $outOfStockQuery = BranchStock::with('ingredient', 'branch')
            ->where('current_quantity', '<=', 0);

        if ($branchId) {
            $outOfStockQuery->where('branch_id', $branchId);
        } elseif ($isManager) {
            $outOfStockQuery->where('branch_id', $user->branch_id);
        }

        $outOfStockItems = $outOfStockQuery->get()->map(fn($stock) => [
            'id' => $stock->id,
            'ingredient' => $stock->ingredient?->name ?? 'Unknown',
            'branch' => $stock->branch?->name ?? 'Unknown',
            'unit' => $stock->ingredient?->unit ?? '',
        ]);

        // ── Waste Log (expired items removed in last 30 days) ──────────
        $wasteQuery = StockMovement::with('branchStock.ingredient', 'branchStock.branch')
            ->where('type', 'waste')
            ->where('created_at', '>=', Carbon::now()->subDays(30));

        if ($branchId) {
            $wasteQuery->whereHas('branchStock', fn($q) => $q->where('branch_id', $branchId));
        } elseif ($isManager) {
            $wasteQuery->whereHas('branchStock', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $recentWaste = $wasteQuery->latest()->take(20)->get();

        // ── Summary Stats ───────────────────────────────────────────────
        $totalExpiring = $expiringItems->where('status', '!=', 'expired')->count();
        $totalExpired = $expiringItems->where('status', 'expired')->count();
        $totalLowStock = $lowStockItems->where('status', '!=', 'normal')->count();
        $totalOutOfStock = $outOfStockItems->count();

        // ── Branches ────────────────────────────────────────────────────
        $branches = $isManager
            ? \App\Models\Branch::where('id', $user->branch_id)->get()
            : \App\Models\Branch::where('status', 'active')->orderBy('name')->get();

        return view('inventory.intelligence', [
            'expiringItems' => $expiringItems,
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
            'recentWaste' => $recentWaste,
            'totalExpiring' => $totalExpiring,
            'totalExpired' => $totalExpired,
            'totalLowStock' => $totalLowStock,
            'totalOutOfStock' => $totalOutOfStock,
            'branchId' => $branchId,
            'branches' => $branches,
        ]);
    }
}
