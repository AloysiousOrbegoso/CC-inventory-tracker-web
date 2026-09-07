<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IngredientController extends Controller
{
    /**
     * Show the ingredient management page.
     */
    public function index(): View
    {
        $ingredients = Ingredient::orderBy('name')->get();

        return view('ingredients.index', [
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Store a new ingredient.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers add ingredients.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('ingredients', 'name')],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json([
            'message'    => 'Ingredient created successfully.',
            'ingredient' => $ingredient,
        ], 201);
    }

    /**
     * Update an existing ingredient.
     */
    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $user = Auth::user();
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers edit ingredients.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('ingredients', 'name')->ignore($ingredient->id)],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $ingredient->update($validated);

        return response()->json([
            'message'    => 'Ingredient updated successfully.',
            'ingredient' => $ingredient->fresh(),
        ]);
    }

    /**
     * Delete an ingredient.
     */
    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers delete ingredients.'], 403);
        }

        // Check if this ingredient is used in any recipes
        if ($ingredient->recipes()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this ingredient because it is used in one or more recipes. Remove it from all recipes first.',
            ], 422);
        }

        $ingredient->delete();

        return response()->json([
            'message' => 'Ingredient deleted successfully.',
        ]);
    }
}
