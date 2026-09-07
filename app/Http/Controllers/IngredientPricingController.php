<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientPricingHistory;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IngredientPricingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isManager = $user->isManager();

        $ingredients = Ingredient::with(['suppliers', 'recipes.product'])
            ->orderBy('name')
            ->get();

        $branches = Branch::when($isManager, fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pricing.ingredients', [
            'ingredients' => $ingredients,
            'branches' => $branches,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update pricing data for an ingredient-supplier link.
     * POST /pricing/ingredients/{ingredient}/pricing
     */
    public function updatePricing(Request $request, Ingredient $ingredient): JsonResponse
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers update pricing.'], 403);
        }
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'package_size' => ['nullable', 'numeric', 'min:0'],
            'package_unit' => ['nullable', 'string', 'max:10'],
            'package_price' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'pricing_notes' => ['nullable', 'string', 'max:1000'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        // If setting as primary, unset other primaries for this ingredient
        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            DB::table('ingredient_supplier')
                ->where('ingredient_id', $ingredient->id)
                ->update(['is_primary' => false]);
        }

        // Upsert the pivot row
        $existing = DB::table('ingredient_supplier')
            ->where('ingredient_id', $ingredient->id)
            ->where('supplier_id', $validated['supplier_id'])
            ->first();

        $pivotData = array_merge(
            $validated,
            ['updated_at' => now()]
        );

        if ($existing) {
            DB::table('ingredient_supplier')
                ->where('id', $existing->id)
                ->update($pivotData);
        } else {
            DB::table('ingredient_supplier')->insert(
                array_merge($pivotData, [
                    'ingredient_id' => $ingredient->id,
                    'created_at' => now(),
                ])
            );
        }

        // Record pricing history
        IngredientPricingHistory::create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => $validated['supplier_id'],
            'unit_cost' => $validated['unit_cost'] ?? null,
            'package_size' => $validated['package_size'] ?? null,
            'package_unit' => $validated['package_unit'] ?? null,
            'package_price' => $validated['package_price'] ?? null,
            'delivery_fee' => $validated['delivery_fee'] ?? null,
            'notes' => $validated['pricing_notes'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing updated for ' . $ingredient->name,
        ]);
    }

    /**
     * Get full pricing data for an ingredient (for the edit modal).
     * GET /pricing/ingredients/{ingredient}/data
     */
    public function getPricingData(Ingredient $ingredient): JsonResponse
    {
        $ingredient->load(['suppliers' => function ($q) {
            $q->withPivot('unit_cost', 'is_primary', 'package_size', 'package_unit', 'package_price', 'delivery_fee', 'pricing_notes');
        }]);

        // Pricing history
        $history = IngredientPricingHistory::where('ingredient_id', $ingredient->id)
            ->with('supplier:id,name')
            ->with('recorder:id,name')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'supplier_name' => $h->supplier?->name ?? '—',
                'unit_cost' => $h->unit_cost,
                'package_size' => $h->package_size,
                'package_price' => $h->package_price,
                'delivery_fee' => $h->delivery_fee,
                'notes' => $h->notes,
                'recorded_by' => $h->recorder?->name ?? '—',
                'created_at' => $h->created_at->toIso8601String(),
            ]);

        return response()->json([
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'suppliers' => $ingredient->suppliers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'unit_cost' => $s->pivot->unit_cost,
                'is_primary' => $s->pivot->is_primary,
                'package_size' => $s->pivot->package_size,
                'package_unit' => $s->pivot->package_unit,
                'package_price' => $s->pivot->package_price,
                'delivery_fee' => $s->pivot->delivery_fee,
                'pricing_notes' => $s->pivot->pricing_notes,
            ]),
            'history' => $history,
        ]);
    }
}
