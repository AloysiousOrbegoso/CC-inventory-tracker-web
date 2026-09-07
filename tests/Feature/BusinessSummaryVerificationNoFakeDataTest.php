<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSummaryVerificationNoFakeDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_shows_real_zero_revenue_not_fabricated_1_24_million(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Branch::factory()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('1,240,000');
        $response->assertDontSee('Maria S.');
        $response->assertDontSee('Classic Milk Tea');
        $response->assertDontSee('QC Main Branch');
    }

    public function test_summary_shows_honest_empty_states_when_no_data_exists(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Branch::factory()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('No revenue recorded yet.');
    }

    public function test_verification_shows_not_on_file_when_no_legal_documents_exist(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Branch::factory()->create(['name' => 'Real Branch']);

        $response = $this->actingAs($admin)->get('/legal-papers');

        $response->assertOk();
        $response->assertSee('No legal documents yet');
    }

    public function test_verification_reflects_a_real_uploaded_permit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create(['name' => 'Real Branch']);
        LegalDocument::create([
            'branch_id' => $branch->id,
            'uploaded_by' => $admin->id,
            'title' => 'Mayor\'s Permit',
            'type' => 'permit',
            'file_path' => 'legal-documents/test.pdf',
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($admin)->get('/legal-papers');

        $response->assertOk();
        $response->assertSee('Valid');
        $response->assertSee("Mayor's Permit");
    }

    public function test_verification_flags_an_expired_permit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create(['name' => 'Real Branch']);
        LegalDocument::create([
            'branch_id' => $branch->id,
            'uploaded_by' => $admin->id,
            'title' => 'Mayor\'s Permit',
            'type' => 'permit',
            'file_path' => 'legal-documents/test.pdf',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/legal-papers');

        $response->assertOk();
        $response->assertSee('Expired');
    }
}
