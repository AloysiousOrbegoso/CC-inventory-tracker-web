<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipesCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $manager;
    private User $staff;
    private Branch $branch;
    private Product $product;
    private Ingredient $ingredient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $this->branch = Branch::factory()->create(['name' => 'Recipe Test Branch']);
        $this->owner = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'branch_id' => null,
            'password' => bcrypt('password'),
        ]);
        $this->manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'branch_id' => $this->branch->id,
            'password' => bcrypt('password'),
        ]);
        $this->staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'branch_id' => $this->branch->id,
        ]);
        $this->product = Product::create(['name' => 'Test Drink', 'category' => 'Drinks', 'price' => 65.00]);
        $this->ingredient = Ingredient::create(['name' => 'Sugar', 'unit' => 'g']);
    }

    public function test_get_product_data_requires_auth(): void
    {
        $response = $this->get('/business/recipes/product/'.$this->product->id.'/data');
        $response->assertStatus(302);
    }

    public function test_get_product_data_returns_recipes(): void
    {
        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 30,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/business/recipes/product/'.$this->product->id.'/data');

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Drink')
            ->assertJsonPath('recipes.0.ingredient.name', 'Sugar');
    }

    public function test_update_product_requires_auth(): void
    {
        $response = $this->put('/business/recipes/product/'.$this->product->id, ['name' => 'Updated']);
        $response->assertStatus(302);
    }

    public function test_owner_can_update_product(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/business/recipes/product/'.$this->product->id, [
                'name' => 'Updated Drink', 'category' => 'Specialty', 'price' => 75.00,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'name' => 'Updated Drink']);
    }

    public function test_manager_can_update_product(): void
    {
        $response = $this->actingAs($this->manager)
            ->putJson('/business/recipes/product/'.$this->product->id, ['name' => 'Manager Updated']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'name' => 'Manager Updated']);
    }

    public function test_staff_cannot_update_product(): void
    {
        $response = $this->actingAs($this->staff)
            ->putJson('/business/recipes/product/'.$this->product->id, ['name' => 'Staff Attempt']);

        $response->assertStatus(403);
    }

    public function test_update_product_validates_price(): void
    {
        $response = $this->actingAs($this->owner)
            ->from('/business/recipes')
            ->put('/business/recipes/product/'.$this->product->id, [
                '_token' => 'test-token',
                'price' => -5,
            ]);

        $response->assertStatus(302)->assertSessionHasErrors('price');
    }

    public function test_add_ingredient_to_product(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/business/recipes/product/'.$this->product->id.'/ingredient', [
                'ingredient_id' => $this->ingredient->id,
                'size' => Recipe::SIZE_REGULAR,
                'quantity_required' => 50,
            ]);

        $response->assertStatus(201)->assertJsonPath('ingredient.name', 'Sugar');
        $this->assertDatabaseHas('recipes', [
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
        ]);
    }

    public function test_add_ingredient_updates_existing(): void
    {
        $recipe = Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 30,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/business/recipes/product/'.$this->product->id.'/ingredient', [
                'ingredient_id' => $this->ingredient->id,
                'size' => Recipe::SIZE_REGULAR,
                'quantity_required' => 60,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'quantity_required' => 60]);
        $this->assertDatabaseCount('recipes', 1);
    }

    public function test_add_ingredient_validates_required(): void
    {
        $response = $this->actingAs($this->owner)
            ->from('/business/recipes')
            ->post('/business/recipes/product/'.$this->product->id.'/ingredient', [
                '_token' => 'test-token',
            ]);

        $response->assertStatus(302)->assertSessionHasErrors(['ingredient_id', 'size', 'quantity_required']);
    }

    public function test_update_ingredient_quantity(): void
    {
        $recipe = Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 30,
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson('/business/recipes/ingredient/'.$recipe->id, ['quantity_required' => 45]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'quantity_required' => 45]);
    }

    public function test_remove_ingredient_from_recipe(): void
    {
        $recipe = Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 30,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/business/recipes/ingredient/'.$recipe->id.'/delete');

        $response->assertStatus(200)->assertJsonPath('message', 'Ingredient removed from recipe.');
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_owner_can_delete_product(): void
    {
        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/business/recipes/product/'.$this->product->id.'/delete');

        $response->assertStatus(200)->assertJsonPath('message', 'Product deleted successfully.');
        $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
    }

    public function test_manager_can_delete_product(): void
    {
        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 10,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson('/business/recipes/product/'.$this->product->id.'/delete');

        $response->assertStatus(200)->assertJsonPath('message', 'Product deleted successfully.');
        $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
    }

    public function test_get_product_data_without_recipes(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/business/recipes/product/'.$this->product->id.'/data');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('recipes'));
    }
}
