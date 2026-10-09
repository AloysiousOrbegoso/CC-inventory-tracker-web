<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * First-run setup wizard: the shortest path from an empty account to a
 * working anti-theft pipeline. The engine can't detect anything until
 * ingredients, recipes, and opening stock exist — nothing else in the app
 * creates all three in one place, so a new owner is otherwise stuck.
 *
 * Each step is idempotent-ish and independently skippable: ingredients can
 * be created here or later on /ingredients, products likewise, and opening
 * stock only fills gaps (existing branch_stock rows are never overwritten).
 */
class SetupController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('setup.index', [
            'status' => $this->buildStatus(),
            'ingredients' => Ingredient::orderBy('name')->get(),
            'branches' => Branch::when($user->isManager(), fn ($q) => $q->where('id', $user->branch_id))
                ->orderBy('name')
                ->get(),
            'products' => Product::with('recipes')->latest()->take(10)->get(),
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json($this->buildStatus());
    }

    /**
     * Step 1 — create a raw material.
     */
    public function storeIngredient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('ingredients', 'name')],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json([
            'message' => 'Ingredient added.',
            'ingredient' => $ingredient,
            'status' => $this->buildStatus(),
        ], 201);
    }

    /**
     * Step 2 — create a menu product together with its recipe lines
     * (what gets deducted from stock on every sale).
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
            'category' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_large' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'lines.*.quantity_required' => ['required', 'numeric', 'min:0.001'],
            'lines.*.size' => ['sometimes', 'in:regular,large'],
        ]);

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create([
                'name' => $validated['name'],
                'category' => $validated['category'] ?? null,
                'price' => $validated['price'],
                'price_large' => $validated['price_large'] ?? null,
                'is_active' => true,
            ]);

            foreach ($validated['lines'] as $line) {
                Recipe::create([
                    'product_id' => $product->id,
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity_required' => $line['quantity_required'],
                    'size' => $line['size'] ?? Recipe::SIZE_REGULAR,
                ]);
            }

            return $product;
        });

        return response()->json([
            'message' => 'Product and recipe saved. Sales will now auto-deduct these ingredients.',
            'product' => $product->load('recipes'),
            'status' => $this->buildStatus(),
        ], 201);
    }

    /**
     * Step 3 — opening stock for a branch. Existing stock rows for an
     * ingredient are left untouched so re-running a step can't clobber
     * quantities the team already counted.
     */
    public function storeStock(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.min_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Managers can only seed their own branch, whatever the form said.
        $branchId = $user->isManager() ? (int) $user->branch_id : (int) $validated['branch_id'];

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, $branchId, $user, &$created, &$skipped) {
            foreach ($validated['items'] as $item) {
                $exists = BranchStock::where('branch_id', $branchId)
                    ->where('ingredient_id', $item['ingredient_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $stock = BranchStock::create([
                    'branch_id' => $branchId,
                    'ingredient_id' => $item['ingredient_id'],
                    'current_quantity' => $item['quantity'],
                    'min_threshold' => $item['min_threshold'] ?? 0,
                    'last_updated_at' => now(),
                ]);

                StockMovement::create([
                    'branch_stock_id' => $stock->id,
                    'type' => StockMovement::TYPE_INITIAL,
                    'quantity_change' => $stock->current_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $stock->current_quantity,
                    'user_id' => $user->id,
                    'notes' => 'Opening stock (setup wizard).',
                ]);

                $created++;
            }
        });

        return response()->json([
            'message' => "Opening stock saved: {$created} item(s) added"
                . ($skipped > 0 ? ", {$skipped} already stocked and left as-is." : '.'),
            'created' => $created,
            'skipped' => $skipped,
            'status' => $this->buildStatus(),
        ], 201);
    }

    /**
     * Dismiss the dashboard nudge without completing the steps — some
     * owners set up catalog pages directly instead.
     */
    public function skip(): JsonResponse
    {
        AppSetting::set('setup_skipped_at', now()->toDateTimeString());

        return response()->json(['message' => 'Setup dismissed. You can always reach it at /setup.', 'status' => $this->buildStatus()]);
    }

    /**
     * Which steps still need attention. Deliberately computed live from the
     * data (not a persisted progress record) so deleting your only product
     * honestly re-opens the step.
     *
     * @return array{has_ingredients: bool, has_products: bool, has_stock: bool, complete: bool, skipped: bool}
     */
    private function buildStatus(): array
    {
        $hasIngredients = Ingredient::exists();
        $hasProducts = Product::exists();
        $hasStock = BranchStock::exists();

        return [
            'has_ingredients' => $hasIngredients,
            'has_products' => $hasProducts,
            'has_stock' => $hasStock,
            'complete' => $hasIngredients && $hasProducts && $hasStock,
            'skipped' => AppSetting::get('setup_skipped_at') !== null,
        ];
    }
}
