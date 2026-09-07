<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DiscrepancyAlert;
use App\Models\BranchStock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isManager = $user->isManager();

        $branches = Branch::with(['stocks.ingredient'])
            ->when($isManager, fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();

        // Enrich each branch with summary stats
        $branchesData = $branches->map(function ($branch) {
            $pendingAlerts = DiscrepancyAlert::where('branch_id', $branch->id)
                ->where('status', 'pending')
                ->count();

            $lowStockCount = BranchStock::where('branch_id', $branch->id)
                ->whereColumn('current_quantity', '<=', 'min_threshold')
                ->where('min_threshold', '>', 0)
                ->count();

            $totalStockItems = BranchStock::where('branch_id', $branch->id)->count();

            $todaySales = $branch->transactions()
                ->whereDate('created_at', now()->toDateString())
                ->sum('total_amount');

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'status' => $branch->status,
                'pending_alerts' => $pendingAlerts,
                'low_stock_count' => $lowStockCount,
                'total_stock_items' => $totalStockItems,
                'today_sales' => $todaySales,
                'staff_count' => $branch->users()->count(),
            ];
        });

        // Suppliers with coordinates for map layer
        $suppliers = Supplier::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount('ingredients')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'address' => $s->address,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
                'contact_person' => $s->contact_person,
                'contact_number' => $s->contact_number,
                'ingredient_count' => $s->ingredients_count,
            ]);

        return view('map.index', [
            'branches' => $branchesData,
            'suppliers' => $suppliers,
        ]);
    }
}
