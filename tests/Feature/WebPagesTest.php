<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\DiscrepancyAlert;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\ShiftLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $manager;
    private User $staff;
    private Branch $branch;
    private Branch $otherBranch;
    private Product $product;
    private Ingredient $ingredient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $this->branch = Branch::factory()->create(['name' => 'Web Test Branch']);
        $this->otherBranch = Branch::factory()->create(['name' => 'Other Branch']);
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
        $this->ingredient = Ingredient::create(['name' => 'Test Ingredient', 'unit' => 'g']);
        $this->product = Product::create(['name' => 'Test Product', 'price' => 50.00]);
        Recipe::create([
            'product_id' => $this->product->id,
            'ingredient_id' => $this->ingredient->id,
            'size' => Recipe::SIZE_REGULAR,
            'quantity_required' => 10,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AUTH VIEWS
    // ═══════════════════════════════════════════════════════════════════

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_login_returns_page_for_authenticated_users(): void
    {
        $response = $this->actingAs($this->owner)->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_step_1_page_loads(): void
    {
        $response = $this->get('/auth/register/step-1');
        $response->assertStatus(200);
    }

    public function test_register_step_2_page_loads(): void
    {
        $response = $this->get('/auth/register/step-2');
        $response->assertStatus(200);
    }

    public function test_register_step_3_page_loads(): void
    {
        $response = $this->get('/auth/register/step-3');
        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AUTHENTICATED PAGES — Owner (super_admin)
    // ═══════════════════════════════════════════════════════════════════

    public function test_recipes_page_loads(): void
    {
        $branch = Branch::factory()->create();
        $this->owner->branch_id = $branch->id;
        $this->owner->save();
        $response = $this->actingAs($this->owner)->get('/business/recipes');
        $response->assertStatus(200);
        $response->assertViewIs('business.recipes');
    }

    public function test_inventory_page_loads(): void
    {
        BranchStock::create([
            'branch_id' => $this->branch->id,
            'ingredient_id' => $this->ingredient->id,
            'current_quantity' => 50,
            'min_threshold' => 10,
        ]);

        $response = $this->actingAs($this->owner)->get('/inventory');
        $response->assertStatus(200);
    }

    public function test_branches_index_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/branches');
        $response->assertStatus(200);
        $response->assertSee('Web Test Branch');
        $response->assertSee('Other Branch');
    }

    public function test_branches_show_page_loads(): void
    {
        Transaction::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'user_id' => $this->owner->id,
            'quantity' => 1,
            'total_amount' => 50.00,
            'client_uuid' => 'branch-show-uuid',
        ]);

        $response = $this->actingAs($this->owner)->get('/branches/'.$this->branch->id);
        $response->assertStatus(200);
        $response->assertSee('Web Test Branch');
    }

    public function test_branches_show_with_recipe_tab(): void
    {
        $response = $this->actingAs($this->owner)->get('/branches/'.$this->branch->id.'?tab=recipe');
        $response->assertStatus(200);
    }

    public function test_branches_show_with_workers_tab(): void
    {
        $response = $this->actingAs($this->owner)->get('/branches/'.$this->branch->id.'?tab=workers');
        $response->assertStatus(200);
    }

    public function test_branches_show_with_logistics_tab(): void
    {
        $response = $this->actingAs($this->owner)->get('/branches/'.$this->branch->id.'?tab=logistics');
        $response->assertStatus(200);
    }

    public function test_alerts_page_loads(): void
    {
        DiscrepancyAlert::create([
            'branch_id' => $this->branch->id,
            'type' => 'stock_mismatch',
            'severity' => 'high',
            'status' => 'pending',
            'details' => 'Test alert on alerts page',
        ]);

        $response = $this->actingAs($this->owner)->get('/alerts');
        $response->assertStatus(200);
    }

    public function test_analytics_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_calendar_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/calendar');
        $response->assertStatus(200);
    }

    public function test_reports_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/reports');
        $response->assertStatus(200);
    }

    public function test_payments_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/payments');
        $response->assertStatus(200);
    }

    public function test_help_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/help');
        $response->assertStatus(200);
    }

    public function test_about_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/about');
        $response->assertStatus(200);
    }

    public function test_business_workers_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/branches/'.$this->branch->id.'?tab=workers');
        $response->assertStatus(200);
        $response->assertSee($this->manager->name);
        $response->assertSee($this->staff->name);
    }

    public function test_logistics_page_loads(): void
    {
        BranchStock::create([
            'branch_id' => $this->branch->id,
            'ingredient_id' => $this->ingredient->id,
            'current_quantity' => 50,
            'min_threshold' => 10,
        ]);

        $response = $this->actingAs($this->owner)->get('/logistics');
        $response->assertStatus(200);
    }

    public function test_verification_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/legal-papers');
        $response->assertStatus(200);
    }

    public function test_settings_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/settings');
        $response->assertStatus(200);
    }

    public function test_api_docs_page_loads(): void
    {
        $response = $this->actingAs($this->owner)->get('/api-docs');
        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  MANAGER SCOPE — Only sees their own branch data
    // ═══════════════════════════════════════════════════════════════════

    public function test_manager_sees_only_their_branch_in_branches_page(): void
    {
        $response = $this->actingAs($this->manager)->get('/branches');
        $response->assertStatus(200);
        $response->assertSee('Web Test Branch');
        $response->assertDontSee('Other Branch');
    }

    public function test_manager_cannot_access_other_branch_show(): void
    {
        $response = $this->actingAs($this->manager)->get('/branches/'.$this->otherBranch->id);
        $response->assertStatus(403);
    }

    public function test_manager_can_access_their_branch_show(): void
    {
        $response = $this->actingAs($this->manager)->get('/branches/'.$this->branch->id);
        $response->assertStatus(200);
    }

    public function test_manager_sees_only_their_workers(): void
    {
        // Create a staff in the other branch
        User::factory()->create([
            'role' => User::ROLE_STAFF,
            'branch_id' => $this->otherBranch->id,
        ]);

        $response = $this->actingAs($this->manager)->get('/business/workers');
        $response->assertStatus(200);
        $response->assertSee($this->staff->name);
        $response->assertDontSee('Other Branch');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  STAFF RESTRICTIONS
    // ═══════════════════════════════════════════════════════════════════

    public function test_staff_cannot_access_workers_page(): void
    {
        // Staff can't access the workers directory at all — route requires manager/owner role
        $response = $this->actingAs($this->staff)->get('/business/workers');
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AUTH — ALL PAGES REQUIRE LOGIN
    // ═══════════════════════════════════════════════════════════════════

    public function test_pages_redirect_when_unauthenticated(): void
    {
        $this->get('/dashboard')->assertStatus(302);
        $this->get('/business/recipes')->assertStatus(302);
        $this->get('/inventory')->assertStatus(302);
        $this->get('/branches')->assertStatus(302);
        $this->get('/alerts')->assertStatus(302);
        $this->get('/analytics')->assertStatus(302);
        $this->get('/calendar')->assertStatus(302);
        $this->get('/reports')->assertStatus(302);
        $this->get('/payments')->assertStatus(302);
        $this->get('/help')->assertStatus(302);
        $this->get('/about')->assertStatus(302);
        $this->get('/business/workers')->assertStatus(302);
        $this->get('/logistics')->assertStatus(302);
        $this->get('/legal-papers')->assertStatus(302);
        $this->get('/settings')->assertStatus(302);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ═══════════════════════════════════════════════════════════════════

    public function test_logout_logs_user_out(): void
    {
        $response = $this->actingAs($this->owner)->post('/logout');
        $response->assertStatus(302);
        $this->assertGuest();
    }
}
