<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InterpretationRange;
use App\Models\InterpretationRangeAuditLog;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterpretationRangeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory($name = 'Test Category'): QuestionCategory
    {
        $ay = AcademicYear::firstOrCreate(['label' => '2025-2026'], ['is_current' => true]);
        return QuestionCategory::create([
            'academic_year_id' => $ay->id,
            'name' => $name,
            'year_level' => '3rd',
            'display_order' => 1,
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 21,
        ]);
    }

    public function test_counselor_cannot_manage_interpretation_ranges()
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $category = $this->createCategory();

        $response = $this->actingAs($counselor)->post(route('interpretation-ranges.store', $category), [
            'min_score' => 0,
            'max_score' => 5,
            'label' => 'Normal',
            'color_tag' => 'green',
            'display_order' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_idor_blocks_updating_range_under_mismatched_category()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $catA = $this->createCategory('Category A');
        $catB = $this->createCategory('Category B');

        $rangeA = InterpretationRange::create([
            'question_category_id' => $catA->id,
            'min_score' => 0,
            'max_score' => 7,
            'label' => 'Normal',
            'color_tag' => 'green',
            'display_order' => 1,
            'is_official_default' => false,
            'created_by' => $admin->id,
        ]);

        // Attempt to update rangeA through catB
        $response = $this->actingAs($admin)->put(route('interpretation-ranges.update', [
            'question_category' => $catB,
            'range' => $rangeA,
        ]), [
            'min_score' => 0,
            'max_score' => 10,
            'label' => 'Hacked',
            'color_tag' => 'red',
            'display_order' => 1,
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('interpretation_ranges', ['label' => 'Hacked']);
    }

    public function test_idor_blocks_deleting_range_under_mismatched_category()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $catA = $this->createCategory('Category A');
        $catB = $this->createCategory('Category B');

        $rangeA = InterpretationRange::create([
            'question_category_id' => $catA->id,
            'min_score' => 0,
            'max_score' => 7,
            'label' => 'Normal',
            'color_tag' => 'green',
            'display_order' => 1,
            'is_official_default' => false,
            'created_by' => $admin->id,
        ]);

        // Attempt to delete rangeA through catB
        $response = $this->actingAs($admin)->delete(route('interpretation-ranges.destroy', [
            'question_category' => $catB,
            'range' => $rangeA,
        ]));

        $response->assertStatus(404);
        $this->assertDatabaseHas('interpretation_ranges', ['id' => $rangeA->id]);
    }

    public function test_updating_official_default_creates_audit_log_in_transaction()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $cat = $this->createCategory('Clinical Scale');

        $range = InterpretationRange::create([
            'question_category_id' => $cat->id,
            'min_score' => 0,
            'max_score' => 7,
            'label' => 'Normal',
            'color_tag' => 'green',
            'display_order' => 1,
            'is_official_default' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('interpretation-ranges.update', [
            'question_category' => $cat,
            'range' => $range,
        ]), [
            'min_score' => 0,
            'max_score' => 8,
            'label' => 'Updated Normal',
            'color_tag' => 'green',
            'display_order' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $range->refresh();
        $this->assertEquals('Updated Normal', $range->label);
        $this->assertEquals(8, $range->max_score);
        $this->assertFalse((bool) $range->is_official_default);

        $this->assertDatabaseHas('interpretation_range_audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'edited_official_default',
        ]);
    }
}
