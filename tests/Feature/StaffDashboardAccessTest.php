<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_the_owner_dashboard(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);

        $response = $this->actingAs($staff)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_staff_can_still_access_their_own_dashboard(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertOk();
    }

    public function test_manager_can_access_the_owner_dashboard(): void
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->manager()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($manager)->get('/dashboard');

        $response->assertOk();
    }

    public function test_super_admin_can_access_the_owner_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
    }

    public function test_staff_only_sees_notices_for_their_own_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $staffA = User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branchA->id]);
        Notice::create(['branch_id' => $branchA->id, 'posted_by' => $staffA->id, 'title' => 'For Branch A', 'body' => 'Body']);
        Notice::create(['branch_id' => $branchB->id, 'posted_by' => $staffA->id, 'title' => 'For Branch B', 'body' => 'Body']);
        Notice::create(['branch_id' => null, 'posted_by' => $staffA->id, 'title' => 'Company Wide', 'body' => 'Body']);

        $response = $this->actingAs($staffA)->get('/mail');

        $response->assertOk();
        $response->assertSee('For Branch A');
        $response->assertSee('Company Wide');
        $response->assertDontSee('For Branch B');
    }
}
