<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\PurchaseHistory;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::withCount('ingredients')
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::orderBy('name')->get();

        return view('suppliers.index', compact('suppliers', 'ingredients'));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers add suppliers.'], 403);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('suppliers', 'public');
        }
        unset($validated['photo']);

        $supplier = Supplier::create($validated);

        return response()->json(['success' => true, 'supplier' => $supplier], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load(['ingredients', 'purchaseHistory' => fn ($q) => $q->latest('purchased_at')->limit(20)]);

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers edit suppliers.'], 403);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            // Replace rather than accumulate — a supplier keeps one photo.
            if ($supplier->photo_path) {
                Storage::disk('public')->delete($supplier->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('suppliers', 'public');
        }
        unset($validated['photo']);

        $supplier->update($validated);

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $user = $request->user();
        if (! $user->isManager() && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($supplier->photo_path) {
            Storage::disk('public')->delete($supplier->photo_path);
        }

        $supplier->delete();

        return response()->json(['success' => true]);
    }

    public function linkIngredient(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            $supplier->ingredients()
                ->wherePivot('ingredient_id', $validated['ingredient_id'])
                ->detach();

            \DB::table('ingredient_supplier')
                ->where('ingredient_id', $validated['ingredient_id'])
                ->update(['is_primary' => false]);
        }

        $supplier->ingredients()->syncWithoutDetaching([
            $validated['ingredient_id'] => [
                'unit_cost' => $validated['unit_cost'] ?? null,
                'is_primary' => $request->boolean('is_primary'),
            ],
        ]);

        return response()->json(['success' => true]);
    }

    public function unlinkIngredient(Supplier $supplier, Ingredient $ingredient): JsonResponse
    {
        $supplier->ingredients()->detach($ingredient->id);

        return response()->json(['success' => true]);
    }

    public function addPurchase(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = PurchaseHistory::create([
            ...$validated,
            'supplier_id' => $supplier->id,
            'recorded_by' => auth()->id(),
        ]);

        $supplier->ingredients()->syncWithoutDetaching([
            $validated['ingredient_id'] => ['unit_cost' => $validated['unit_price']],
        ]);

        return response()->json(['success' => true, 'purchase' => $purchase], 201);
    }
}
