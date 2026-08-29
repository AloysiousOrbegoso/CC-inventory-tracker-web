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

class RecipeCostHiddenFromStaffTest extends TestCase
{
    use RefreshDatabase;

    private function makeProductWithCost(): Product
    {
        $product = Product::create(['name' => 'Milk Tea', 'category' => 'Drinks', 'price' => 100, 'is_active' => true]);
        $ingredient = Ingredient::create(['name' => 'Milk Powder', 'unit' => 'g']);
        $supplier = Supplier::create(['name' => 'Dairy Co', 'is_active' => true]);
        $supplier->ingredients()->attach($ingredient->id, ['unit_cost' => 2, 'is_primary' => true]);
        Recipe::create(['product_id' => $product->id, 'ingredient_id' => $ingredient->id, 'size' => 'regular', 'quantity_required' => 10]);

        return $product;
    }

    public function test_staff_cannot_reach_the_recipes_page_at_all(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);

        $this->actingAs($staff)->get('/business/recipes')->assertForbidden();
        $this->actingAs($staff)->get('/recipes')->assertForbidden();
    }

    public function test_staff_cannot_reach_the_recipe_crud_endpoints(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);
        $product = $this->makeProductWithCost();

        $this->actingAs($staff)->getJson("/business/recipes/product/{$product->id}/data")->assertForbidden();
        $this->actingAs($staff)->getJson("/business/recipes/product/{$product->id}/profile")->assertForbidden();
        $this->actingAs($staff)->putJson("/business/recipes/product/{$product->id}", ['price' => 999])->assertForbidden();
    }

    public function test_manager_can_still_reach_the_recipes_page_with_cost_data(): void
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->manager()->create(['branch_id' => $branch->id]);
        $product = $this->makeProductWithCost();

        $response = $this->actingAs($manager)->get('/business/recipes');

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertNotNull($products->firstWhere('id', $product->id)->cost_breakdown);
        $response->assertSee('Margin');
        $response->assertSee('Profile');
    }

    public function test_manager_can_still_hit_the_ingredient_profile_endpoint(): void
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->manager()->create(['branch_id' => $branch->id]);
        $product = $this->makeProductWithCost();

        $response = $this->actingAs($manager)->getJson("/business/recipes/product/{$product->id}/profile");

        $response->assertOk();
    }
}
