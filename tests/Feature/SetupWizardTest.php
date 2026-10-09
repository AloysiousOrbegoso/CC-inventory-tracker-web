<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_brand_new_account_sees_every_step_pending(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Branch::factory()->create();

        $response = $this->actingAs($admin)->get('/setup');

        $response->assertOk();
        $response->assertSee('0 on file');
        $response->assertDontSee('Pipeline live!');
    }

    public function test_status_endpoint_reflects_an_empty_account(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->getJson('/setup/status');

        $response->assertOk();
        $response->assertJson([
            'has_ingredients' => false,
            'has_products' => false,
            'has_stock' => false,
            'complete' => false,
        ]);
    }

    public function test_adding_an_ingredient_advances_step_one(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->postJson('/setup/ingredients', [
            'name' => 'Tapioca Pearls',
            'unit' => 'g',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status.has_ingredients', true);
        $response->assertJsonPath('status.complete', false);
        $this->assertDatabaseHas('ingredients', ['name' => 'Tapioca Pearls']);
    }

    public function test_full_wizard_flow_reaches_complete(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create();

        $ingredient = Ingredient::create(['name' => 'Milk Powder', 'unit' => 'g']);

        $productResponse = $this->actingAs($admin)->postJson('/setup/products', [
            'name' => 'Classic Milk Tea',
            'category' => 'Drinks',
            'price' => 89,
            'lines' => [
                ['ingredient_id' => $ingredient->id, 'quantity_required' => 20, 'size' => 'regular'],
            ],
        ]);
        $productResponse->assertCreated();
        $productResponse->assertJsonPath('status.has_products', true);

        $stockResponse = $this->actingAs($admin)->postJson('/setup/stock', [
            'branch_id' => $branch->id,
            'items' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 500, 'min_threshold' => 50],
            ],
        ]);
        $stockResponse->assertCreated();
        $stockResponse->assertJsonPath('status.has_stock', true);
        $stockResponse->assertJsonPath('status.complete', true);

        $page = $this->actingAs($admin)->get('/setup');
        $page->assertOk();
        $page->assertSee('Pipeline live!');
    }
}
