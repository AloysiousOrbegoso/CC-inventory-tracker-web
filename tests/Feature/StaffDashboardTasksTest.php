<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ShiftLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTasksTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaff(Branch $branch): User
    {
        return User::factory()->create(['role' => User::ROLE_STAFF, 'branch_id' => $branch->id]);
    }

    private function openShiftFor(User $staff, Branch $branch): ShiftLog
    {
        return ShiftLog::create([
            'branch_id' => $branch->id,
            'user_id' => $staff->id,
            'shift_start' => now(),
            'status' => 'open',
        ]);
    }

    public function test_verify_till_records_the_opening_amount(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);
        $shift = $this->openShiftFor($staff, $branch);

        $response = $this->actingAs($staff)->post('/staff/verify-till', ['till_amount' => 1500.50]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertEquals(1500.50, $shift->fresh()->opening_till_amount);
    }

    public function test_verify_till_requires_an_open_shift(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);

        $response = $this->actingAs($staff)->post('/staff/verify-till', ['till_amount' => 1000]);

        $response->assertRedirect(route('staff.dashboard'));
        $response->assertSessionHas('status', 'Open your shift first.');
    }

    public function test_mark_prep_done_records_a_timestamp(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);
        $shift = $this->openShiftFor($staff, $branch);

        $response = $this->actingAs($staff)->post('/staff/mark-prep-done');

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertNotNull($shift->fresh()->prep_completed_at);
    }

    public function test_mark_clean_done_records_a_timestamp(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);
        $shift = $this->openShiftFor($staff, $branch);

        $response = $this->actingAs($staff)->post('/staff/mark-clean-done');

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertNotNull($shift->fresh()->cleaning_completed_at);
    }

    public function test_dashboard_reflects_completed_tasks(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);
        $shift = $this->openShiftFor($staff, $branch);
        $shift->update([
            'opening_till_amount' => 2000,
            'prep_completed_at' => now(),
            'cleaning_completed_at' => now(),
        ]);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertOk();
        $response->assertViewHas('hasVerifiedTill', true);
        $response->assertViewHas('hasCompletedPrep', true);
        $response->assertViewHas('hasCompletedClean', true);
    }

    public function test_dashboard_no_longer_shows_coming_soon_stubs(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->makeStaff($branch);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertOk();
        $response->assertDontSee('comingSoon');
    }
}
