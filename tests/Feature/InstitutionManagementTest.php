<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InstitutionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_counselor_cannot_access_institution_settings()
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($counselor)->get('/manage/institution');
        $response->assertStatus(403);
    }

    public function test_admin_can_set_current_academic_year_in_transaction_and_clears_cache()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ayOld = AcademicYear::create(['label' => '2024-2025', 'is_current' => true]);
        $ayNew = AcademicYear::create(['label' => '2025-2026', 'is_current' => false]);

        Cache::put('current_academic_year', 'cached_dummy', 3600);
        Cache::put('filter_academic_years', 'cached_dummy', 3600);

        $response = $this->actingAs($admin)->patch(route('institution.academic-years.set-current', $ayNew));

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('status', 'Active Academic Year has been set.');

        $this->assertDatabaseHas('academic_years', [
            'id' => $ayNew->id,
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('academic_years', [
            'id' => $ayOld->id,
            'is_current' => false,
        ]);

        $this->assertEquals(1, AcademicYear::where('is_current', true)->count());
        $this->assertFalse(Cache::has('current_academic_year'));
        $this->assertFalse(Cache::has('filter_academic_years'));
    }

    public function test_cannot_delete_active_academic_year()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ayActive = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->delete(route('institution.academic-years.destroy', $ayActive));

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('academic_years', [
            'id' => $ayActive->id,
        ]);
    }
}
