<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeCostBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipes_index_shows_real_inline_cost_and_margin_per_size(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create();
        $admin->branch_id = $branch->id;
        $admin->save();

        $product = Product::create(['name' => 'Milk Tea', 'category' => 'Drinks', 'price' => 100, 'price_large' => 150, 'is_active' => true]);
        $ingredient = Ingredient::create(['name' => 'Milk Powder', 'unit' => 'g']);
        $supplier = Supplier::create(['name' => 'Dairy Co', 'is_active' => true]);
        $supplier->ingredients()->attach($ingredient->id, ['unit_cost' => 2, 'is_primary' => true]);

        Recipe::create(['product_id' => $product->id, 'ingredient_id' => $ingredient->id, 'size' => 'regular', 'quantity_required' => 10]);
        Recipe::create(['product_id' => $product->id, 'ingredient_id' => $ingredient->id, 'size' => 'large', 'quantity_required' => 20]);

        $response = $this->actingAs($admin)->get('/business/recipes');

        $response->assertOk();
        $products = $response->viewData('products');
        $breakdown = $products->firstWhere('id', $product->id)->cost_breakdown->keyBy('size');

        // Regular: cost 10*2=20, price 100 -> profit 80, margin 80%.
        $this->assertSame(20.0, $breakdown['regular']['total_cost']);
        $this->assertSame(80.0, $breakdown['regular']['profit']);
        $this->assertSame(80.0, $breakdown['regular']['margin_pct']);

        // Large: cost 20*2=40, price_large 150 (NOT the regular 100) -> profit 110, margin 73.3%.
        $this->assertSame(40.0, $breakdown['large']['total_cost']);
        $this->assertSame(150.0, $breakdown['large']['price']);
        $this->assertSame(110.0, $breakdown['large']['profit']);
        $this->assertSame(73.3, $breakdown['large']['margin_pct']);
    }

    public function test_ingredient_profile_endpoint_uses_the_same_per_size_price_as_the_inline_table(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $product = Product::create(['name' => 'Milk Tea', 'category' => 'Drinks', 'price' => 100, 'price_large' => 150, 'is_active' => true]);
        $ingredient = Ingredient::create(['name' => 'Milk Powder', 'unit' => 'g']);
        $supplier = Supplier::create(['name' => 'Dairy Co', 'is_active' => true]);
        $supplier->ingredients()->attach($ingredient->id, ['unit_cost' => 2, 'is_primary' => true]);

        Recipe::create(['product_id' => $product->id, 'ingredient_id' => $ingredient->id, 'size' => 'large', 'quantity_required' => 20]);

        $response = $this->actingAs($admin)->getJson("/business/recipes/product/{$product->id}/profile");

        $response->assertOk();
        $sizes = collect($response->json('sizes'))->keyBy('size');
        $this->assertEquals(150.0, $sizes['large']['price']);
        $this->assertEquals(110.0, $sizes['large']['profit']);
    }
}
