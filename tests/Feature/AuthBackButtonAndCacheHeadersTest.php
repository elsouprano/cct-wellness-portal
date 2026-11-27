<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthBackButtonAndCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        $dept = Department::create(['name' => 'School of Computer Studies']);
        $prog = Program::create(['department_id' => $dept->id, 'name' => 'BS Information Technology', 'code' => 'BSIT']);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023011068',
            'email' => 'student@citycollegeoftagaytay.edu.ph',
            'password' => Hash::make('SecretPass123!'),
            'department_id' => $dept->id,
            'program_id' => $prog->id,
            'year_level' => '4th',
            'section' => '4-1',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        $cat = QuestionCategory::create([
            'academic_year_id' => $ay->id,
            'year_level' => '4th',
            'name' => 'erq',
            'display_name' => 'Emotion Regulation Questionnaire (ERQ)',
            'display_order' => 1,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 7,
        ]);

        QuestionItem::create([
            'question_category_id' => $cat->id,
            'item_number' => 1,
            'prompt' => 'Test question',
        ]);

        AssessmentSchedule::create([
            'academic_year_id' => $ay->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '23:59',
        ]);
    }

    public function test_guest_auth_pages_send_no_store_cache_headers(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/sample-token'] as $uri) {
            $response = $this->get($uri);
            $response->assertOk();

            $cacheControl = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cacheControl, "Expected no-store on {$uri}");
            $this->assertStringContainsString('no-cache', $cacheControl, "Expected no-cache on {$uri}");
        }
    }

    public function test_signed_in_user_cannot_view_login_register_or_forgot_password(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $guestUri) {
            $response = $this->actingAs($this->student)->get($guestUri);
            $response->assertRedirect(route('dashboard', absolute: false));
        }
    }

    public function test_authenticated_pages_send_no_store_headers_and_logout_blocks_back_access(): void
    {
        foreach (['/dashboard', '/inventory', '/announcements', '/inventory/history', '/profile'] as $protectedUri) {
            $response = $this->actingAs($this->student)->get($protectedUri);
            $response->assertOk();

            $cacheControl = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cacheControl, "Expected no-store on {$protectedUri}");
        }

        // Logout invalidates session and subsequent visits to protected pages redirect to login
        $logout = $this->actingAs($this->student)->post('/logout');
        $logout->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/inventory')->assertRedirect(route('login'));
    }

    public function test_intended_url_redirect_still_works_after_login(): void
    {
        // Guest requests /inventory -> redirected to /login with intended URL stored
        $this->get('/inventory')->assertRedirect(route('login'));

        // Logging in redirects to the intended /inventory URL instead of generic /dashboard
        $login = $this->post('/login', [
            'identifier' => $this->student->email,
            'password' => 'SecretPass123!',
        ]);

        $login->assertRedirect('/inventory');
    }
}
