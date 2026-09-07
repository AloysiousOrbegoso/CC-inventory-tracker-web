<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileNoFakeDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_worker_with_no_profile_data_shows_honest_placeholders_not_fabricated_bio(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create();
        // Deliberately reuse a name that used to have hardcoded fake bio data
        // baked into the view, keyed by literal name string match.
        $worker = User::factory()->create(['name' => 'Juan dela Cruz', 'role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);
        WorkerProfile::create(['user_id' => $worker->id]);

        // The worker directory lists workers — verify fake bio data is not shown
        $response = $this->actingAs($admin)->get('/branches/'.$branch->id.'?tab=workers');

        $response->assertOk();
        $response->assertDontSee('456 Shaw Blvd');
        $response->assertDontSee('UST — BS Hotel');
        $response->assertDontSee('Food handling cert');
    }

    public function test_directory_shows_a_real_empty_state_not_fake_demo_employees_when_no_workers_exist(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        $admin->branch_id = $branch->id;
        $admin->save();

        $response = $this->actingAs($admin)->get('/branches/'.$branch->id.'?tab=workers');

        $response->assertOk();
        $response->assertDontSee('Maria Santos');
        $response->assertDontSee('QC Main Branch');
    }
}
