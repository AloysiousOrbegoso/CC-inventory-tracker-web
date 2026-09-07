<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessRecipesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        if (!$user) {
            // Fall back to auth facade
            $user = auth()->user();
        }
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id', $user->branch_id);

        // Cost, profit, and margin are business intelligence a staff account
        // has no operational need to see -- they need ingredient/quantity
        // data to prep a product, not what it costs the business to make it.
        $canSeeCosts = $user->isSuperAdmin() || $isManager;

        $branches = Branch::when($isManager, fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();

        $categories = Product::distinct()->pluck('category')->filter()->values();

        $products = Product::with('recipes.ingredient.suppliers')
            ->orderBy('name')
            ->get();

        try {
            $this->markAvailability($products, $branches->pluck('id'));

            if ($canSeeCosts) {
                foreach ($products as $product) {
                    $product->cost_breakdown = $this->costBreakdown($product)['sizes'];
                }
            }
        } catch (\Throwable $e) {
            \Log::error('BusinessRecipesController error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        $allIngredients = Ingredient::orderBy('name')->get();

        try {
            return view('business.recipes', [
            'branches'        => $branches,
            'categories'      => $categories,
            'products'        => $products,
            'allIngredients'  => $allIngredients,
            'canSeeCosts'     => $canSeeCosts,
        ]);
        } catch (\Throwable $e) {
            \Log::error('BusinessRecipesView error: ' . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
            throw $e;
        }
    }

    /**
     * Tag each product with whether it can actually be sold right now.
     */
    private function markAvailability($products, $branchIds): void
    {
        if ($branchIds->isEmpty()) {
            foreach ($products as $product) {
                $product->availability = $product->is_active ? 'available' : 'discontinued';
                $product->missing_ingredients = collect();
            }
            return;
        }

        $stock = BranchStock::whereIn('branch_id', $branchIds)
            ->get()
            ->groupBy('ingredient_id')
            ->map(fn ($rows) => (float) $rows->max('current_quantity'));

        foreach ($products as $product) {
            if (! $product->is_active) {
                $product->availability = 'discontinued';
                $product->missing_ingredients = collect();

                continue;
            }

            $missing = $product->recipes
                ->filter(fn ($r) => ($stock[$r->ingredient_id] ?? 0) <= 0)
                ->map(fn ($r) => $r->ingredient?->name)
                ->filter()
                ->unique()
                ->values();

            $product->availability = $missing->isNotEmpty() ? 'out_of_stock' : 'available';
            $product->missing_ingredients = $missing;
        }
    }

    /**
     * Update product metadata (name, category, price, procedure).
     */
    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnerOrManager($product);

        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'category'  => ['nullable', 'string', 'max:255'],
            'price'     => ['sometimes', 'required', 'numeric', 'min:0'],
            'procedure' => ['nullable', 'string'],
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Add or update a recipe ingredient for a specific size.
     */
    public function addIngredient(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnerOrManager($product);

        $validated = $request->validate([
            'ingredient_id'     => ['required', 'exists:ingredients,id'],
            'size'              => ['required', Rule::in([Recipe::SIZE_REGULAR, Recipe::SIZE_LARGE])],
            'quantity_required' => ['required', 'numeric', 'min:0.001'],
        ]);

        // Check if this ingredient+size combo already exists
        $existing = Recipe::where('product_id', $product->id)
            ->where('ingredient_id', $validated['ingredient_id'])
            ->where('size', $validated['size'])
            ->first();

        if ($existing) {
            // Update existing
            $existing->update(['quantity_required' => $validated['quantity_required']]);
            return response()->json($existing->load('ingredient'));
        }

        $recipe = Recipe::create([
            'product_id'        => $product->id,
            'ingredient_id'     => $validated['ingredient_id'],
            'size'              => $validated['size'],
            'quantity_required' => $validated['quantity_required'],
        ]);

        return response()->json($recipe->load('ingredient'), 201);
    }

    /**
     * Update a recipe ingredient's quantity.
     */
    public function updateIngredient(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorizeOwnerOrManager($recipe->product);

        $validated = $request->validate([
            'quantity_required' => ['required', 'numeric', 'min:0.001'],
        ]);

        $recipe->update($validated);

        return response()->json($recipe->load('ingredient'));
    }

    /**
     * Return full product data with recipes as JSON.
     */
    public function getProductData(Product $product): JsonResponse
    {
        $product->load('recipes.ingredient');
        return response()->json($product);
    }

    /**
     * Ingredient profile drill-down: full cost breakdown with supplier info.
     */
    public function ingredientProfile(Product $product): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user->isSuperAdmin() || $user->isManager(), 403, 'You do not have permission to view cost data.');

        $product->load('recipes.ingredient.suppliers');

        $breakdown = $this->costBreakdown($product);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
            ],
            'ingredients' => $breakdown['ingredients'],
            'sizes' => $breakdown['sizes'],
        ]);
    }

    /**
     * Ingredient cost + margin, per recipe size, shared by the ingredient-profile
     * drill-down and the recipe table's inline cost column — one source of truth.
     * Requires 'recipes.ingredient.suppliers' to already be eager-loaded.
     *
     * @return array{ingredients: \Illuminate\Support\Collection, sizes: \Illuminate\Support\Collection}
     */
    private function costBreakdown(Product $product): array
    {
        $ingredients = $product->recipes->map(function ($recipe) {
            $ingredient = $recipe->ingredient;
            $primarySupplier = $ingredient?->suppliers->firstWhere('pivot.is_primary', true);

            $unitCost = $primarySupplier?->pivot?->unit_cost ?? 0;
            $lineCost = $unitCost * (float) $recipe->quantity_required;

            return [
                'ingredient_id' => $ingredient?->id,
                'name' => $ingredient?->name,
                'unit' => $ingredient?->unit,
                'size' => $recipe->size,
                'quantity_required' => (float) $recipe->quantity_required,
                'unit_cost' => (float) $unitCost,
                'line_cost' => round($lineCost, 2),
                'supplier' => $primarySupplier ? [
                    'id' => $primarySupplier->id,
                    'name' => $primarySupplier->name,
                    'contact_number' => $primarySupplier->contact_number,
                ] : null,
            ];
        });

        // A serving is one size or the other — never both, so cost is summed per size,
        // against that size's own selling price (Large sizes use price_large, not price).
        $sizes = $ingredients->groupBy('size')->map(function ($lines, $size) use ($product) {
            $totalCost = round($lines->sum('line_cost'), 2);
            $price = $product->priceForSize($size);

            return [
                'size' => $size,
                'total_cost' => $totalCost,
                'price' => $price,
                'profit' => round($price - $totalCost, 2),
                'margin_pct' => $price > 0 ? round((($price - $totalCost) / $price) * 100, 1) : 0,
                'suggested_price_65' => $totalCost > 0 ? round($totalCost / 0.35, 2) : 0,
            ];
        })->values();

        return ['ingredients' => $ingredients, 'sizes' => $sizes];
    }

    /**
     * Delete a product and its recipes (super_admin only).
     */
    public function destroyProduct(Product $product): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && ! $user->isManager()) {
            abort(403, 'You do not have permission to delete products.');
        }

        $product->recipes()->delete();
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Remove an ingredient from a recipe.
     */
    public function removeIngredient(Recipe $recipe): JsonResponse
    {
        $this->authorizeOwnerOrManager($recipe->product);

        $recipe->delete();

        return response()->json([
            'message' => 'Ingredient removed from recipe.',
        ]);
    }

    /**
     * Authorize that the current user can edit this product's recipe.
     * Super admins can edit all; managers can only edit their own branch's products.
     */
    private function authorizeOwnerOrManager(Product $product): void
    {
        $user = Auth::user();

        // Owners (super_admin) and managers can edit recipes
        if (! $user->isSuperAdmin() && ! $user->isManager()) {
            abort(403, 'You do not have permission to edit recipes.');
        }
    }
}
