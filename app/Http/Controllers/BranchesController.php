<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ShiftStockCount;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchesController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $branches = Branch::with([
            'transactions' => fn ($q) => $q->whereDate('created_at', today()),
            'users' => fn ($q) => $q->where('role', 'staff'),
        ])
            ->where('status', 'active')
            ->when($user->isManager(), fn ($q) => $q->where('id', $user->branch_id))
            ->get();

        // Get products with recipes for the recipes panel
        $products = Product::with('recipes.ingredient')->get();

        return view('branches.index', [
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'street_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // Build a combined location string from structured address
        $locationParts = array_filter([
            $validated['street_address'] ?? null,
            $validated['city'] ?? null,
            $validated['province'] ?? null,
            $validated['zip_code'] ?? null,
        ]);
        $combinedLocation = !empty($locationParts) ? implode(', ', $locationParts) : null;

        $branch = Branch::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'location' => $combinedLocation,
            'street_address' => $validated['street_address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => 'active',
        ]);

        // Auto-geocode the address if we have enough info
        if ($combinedLocation) {
            $coords = $this->geocodeAddress($combinedLocation);
            if ($coords) {
                $branch->update([
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ]);
            }
        }

        return redirect()->route('branches')
            ->with('success', 'Business added successfully!');
    }

    /**
     * Geocode an address string via Nominatim.
     */
    private function geocodeAddress(string $address): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'InvenTrack/1.0 (inventory-tracker)',
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address . ', Philippines',
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 0,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Geocoding failed for branch: ' . $address, ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function updateDescription(Request $request, Branch $branch)
    {
        $user = auth()->user();
        abort_if($user->isManager() && $branch->id !== $user->branch_id, 403);

        $validated = $request->validate([
            'description' => ['sometimes', 'nullable', 'string'],
            'services' => ['sometimes', 'nullable', 'string'],
        ]);

        $updates = [];
        if (array_key_exists('description', $validated)) {
            $description = trim((string) ($validated['description'] ?? ''));
            $updates['description'] = $description !== '' ? $description : null;
        }
        if (array_key_exists('services', $validated)) {
            $services = trim((string) ($validated['services'] ?? ''));
            $updates['services'] = $services !== '' ? $services : null;
        }

        $branch->update($updates);

        return response()->json(['success' => true, 'branch' => $branch]);
    }

    public function disown(Branch $branch)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $branch->update(['status' => 'inactive']);

        return response()->json(['success' => true, 'branch' => $branch]);
    }

    public function show(Branch $branch, Request $request): View
    {
        $user = auth()->user();
        abort_if($user->isManager() && $branch->id !== $user->branch_id, 403);

        $tab = in_array($request->query('tab'), ['analytics', 'recipe', 'workers', 'logistics'], true)
            ? $request->query('tab')
            : 'analytics';

        $data = match ($tab) {
            'analytics' => [
                'recent_transactions' => $branch->transactions()->with('product', 'user')->latest()->take(10)->get(),
                'daily_sales' => $branch->transactions()
                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                    ->whereDate('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'total_revenue' => $branch->transactions()->sum('total_amount'),
                'leakage_rows' => ShiftStockCount::with('ingredient', 'shiftLog.user')
                    ->whereHas('shiftLog', fn ($q) => $q->where('branch_id', $branch->id))
                    ->where('variance', '<', 0)
                    ->latest()
                    ->take(8)
                    ->get(),
                'monthly_sales' => $branch->transactions()
                    ->whereYear('created_at', now()->year)
                    ->get()
                    ->groupBy(fn ($t) => $t->created_at->format('Y-m'))
                    ->map(fn ($g) => $g->sum('total_amount'))
                    ->sortKeys(),
            ],
            'recipe' => [
                'products' => Product::with('recipes.ingredient')->get(),
            ],
            'workers' => [
                'workers' => $branch->users()->orderBy('name')->get(),
            ],
            'logistics' => [
                'stocks' => $branch->stocks()->with('ingredient')->get(),
                'movements' => StockMovement::with('branchStock.ingredient', 'user')
                    ->whereHas('branchStock', fn ($q) => $q->where('branch_id', $branch->id))
                    ->latest()
                    ->take(20)
                    ->get(),
            ],
        };

        return view('branches.show', $data + [
            'branch' => $branch,
            'tab' => $tab,
            'all_branches' => Branch::orderBy('name')->get(['id', 'name', 'status']),
        ]);
    }
}
