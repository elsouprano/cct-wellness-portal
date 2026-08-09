<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventorySubmissionTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_cannot_submit_twice_per_academic_year(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);
        
        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/inventory', []);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('status', 'Already submitted.');
    }

    public function test_partial_submission_fails_validation(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        // Submit only 1 response
        $response = $this->actingAs($user)->post('/inventory', [
            'responses' => [
                'learning_style' => [1 => 1]
            ],
            'timings' => [
                'learning_style' => [1 => 1500]
            ]
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_db_unique_constraint_on_submission(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);
    }

    public function test_validate_section_fails_on_missing_items(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'learning_style',
            'responses' => [
                'learning_style' => [
                    1 => 1 // missing items 2-14
                ]
            ]
        ]);

        $response->assertStatus(422);
    }

    public function test_validate_section_succeeds_when_all_items_present(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        $responses = [];
        for ($i = 1; $i <= 14; $i++) {
            $responses[$i] = 1;
        }

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'learning_style',
            'responses' => [
                'learning_style' => $responses
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
