<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\StudentPromotionBatch;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentPromotionCsvTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $counselor;

    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'School of Computer Studies']);
        $this->program = Program::create([
            'department_id' => $dept->id,
            'name' => 'BS in Information Technology',
            'code' => 'BSIT',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_non_admin_users_cannot_access_promotion_pages_or_endpoints(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'email_verified_at' => now(),
        ]);

        // 1. Unauthenticated users are redirected to login
        $this->get('/manage/promotions')->assertRedirect('/login');
        $this->post('/manage/promotions/upload')->assertRedirect('/login');

        // 2. Student is blocked with 403 Forbidden
        $this->actingAs($student)->get('/manage/promotions')->assertStatus(403);
        $this->actingAs($student)->post('/manage/promotions/upload')->assertStatus(403);
        $this->actingAs($student)->post('/manage/promotions/confirm')->assertStatus(403);

        // 3. Counselor is blocked with 403 Forbidden (System Admin only)
        $this->actingAs($this->counselor)->get('/manage/promotions')->assertStatus(403);
        $this->actingAs($this->counselor)->post('/manage/promotions/upload')->assertStatus(403);
    }

    public function test_admin_can_view_promotions_page_and_download_sample_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/promotions');
        $response->assertStatus(200);
        $response->assertSee('Promote Students via CSV');
        $response->assertSee('student_number,year_level');

        $download = $this->actingAs($this->admin)->get('/manage/promotions/sample-csv');
        $download->assertStatus(200);
        $download->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $download->streamedContent();
        $this->assertStringContainsString('student_number,year_level', $content);
        $this->assertStringContainsString('2023-0001,2nd', $content);
    }

    public function test_invalid_header_is_rejected(): void
    {
        $csvContent = "student_id,year_level\n2023-0001,2nd\n";
        $file = UploadedFile::fake()->createWithContent('wrong_header.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/manage/promotions/upload', [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors('csv_file');
        $this->assertNull(session('promotion_preview'));
    }

    public function test_non_csv_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post('/manage/promotions/upload', [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_duplicate_rows_in_file_are_marked_invalid(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
        ]);

        $csvContent = "student_number,year_level\n2023-0001,2nd\n2023-0001,3rd\n";
        $file = UploadedFile::fake()->createWithContent('dupes.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/manage/promotions/upload', [
            'csv_file' => $file,
        ]);

        $response->assertRedirect('/manage/promotions');
        $preview = session('promotion_preview');
        $this->assertNotNull($preview);
        $this->assertCount(1, $preview['valid_rows']);
        $this->assertCount(1, $preview['invalid_rows']);
        $this->assertStringContainsString('Duplicate student number', $preview['invalid_rows'][0]['reason']);
    }

    public function test_cells_with_csv_injection_formulas_are_rejected(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'program' => 'BSIT',
            'year_level' => '1st',
        ]);

        $csvContent = "student_number,year_level\n2023-0001,=cmd|' /C calc'!A0\n@inject,2nd\n";
        $file = UploadedFile::fake()->createWithContent('injection.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/manage/promotions/upload', [
            'csv_file' => $file,
        ]);

        $preview = session('promotion_preview');
        $this->assertCount(0, $preview['valid_rows']);
        $this->assertCount(2, $preview['invalid_rows']);
        $this->assertStringContainsString('forbidden formula character', $preview['invalid_rows'][0]['reason']);
        $this->assertStringContainsString('forbidden formula character', $preview['invalid_rows'][1]['reason']);
    }

    public function test_sample_csv_with_at_least_5_rows_including_2_bad_rows_matches_preview_and_confirms_properly(): void
    {
        // Create 3 valid existing students
        $st1 = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
        ]);

        $st2 = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0002',
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '2-1',
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
        ]);

        $st3 = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0003',
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'program' => 'BSIT',
            'year_level' => '3rd',
            'section' => '3-1',
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
        ]);

        // CSV of 6 rows (4 valid student IDs with 1 duplicate, 1 non-existent student, 1 invalid year level = 3 valid, 3 bad)
        // Here we test at least 5 rows with at least 2 bad rows:
        // Row 1 (valid): 2023-0001,2nd
        // Row 2 (valid): 2023-0002,3rd
        // Row 3 (valid): 2023-0003,4th
        // Row 4 (bad - non-existent student): 2023-9999,2nd
        // Row 5 (bad - invalid year level): 2023-0001,5th (also duplicate)
        $csvContent = "student_number,year_level\n".
                      "2023-0001,2nd\n".
                      "2023-0002,3rd\n".
                      "2023-0003,4th\n".
                      "2023-9999,2nd\n".
                      "2023-0001,5th\n";

        $file = UploadedFile::fake()->createWithContent('promotion_test.csv', $csvContent);

        // Upload and check preview
        $response = $this->actingAs($this->admin)->post('/manage/promotions/upload', [
            'csv_file' => $file,
        ]);

        $response->assertRedirect('/manage/promotions');
        $preview = session('promotion_preview');
        $this->assertNotNull($preview);

        // Counts match: 3 valid rows, 2 invalid rows
        $this->assertCount(3, $preview['valid_rows']);
        $this->assertCount(2, $preview['invalid_rows']);

        $viewResponse = $this->actingAs($this->admin)->get('/manage/promotions');
        $viewResponse->assertSee('3 Valid Rows');
        $viewResponse->assertSee('2 Invalid Rows');
        $viewResponse->assertSee('Smith, Alice');
        $viewResponse->assertSee('Jones, Bob');
        $viewResponse->assertSee('Brown, Charlie');
        $viewResponse->assertSee('2023-9999');

        // Test download of invalid rows CSV
        $invalidDownload = $this->actingAs($this->admin)->get('/manage/promotions/invalid-rows-csv');
        $invalidDownload->assertStatus(200);
        $invalidContent = $invalidDownload->streamedContent();
        $this->assertStringContainsString('2023-9999', $invalidContent);
        $this->assertStringContainsString('does not exist in the student records', $invalidContent);

        // Confirm promotion
        $confirmResponse = $this->actingAs($this->admin)->post('/manage/promotions/confirm');
        $confirmResponse->assertRedirect('/manage/promotions');
        $confirmResponse->assertSessionHas('success');

        // Check database updates for each student
        $st1->refresh();
        $st2->refresh();
        $st3->refresh();

        $this->assertEquals('2nd', $st1->year_level);
        $this->assertEmpty($st1->section, 'Old section must be cleared');
        $this->assertTrue((bool) $st1->needs_section_confirmation);

        $this->assertEquals('3rd', $st2->year_level);
        $this->assertEmpty($st2->section, 'Old section must be cleared');
        $this->assertTrue((bool) $st2->needs_section_confirmation);

        $this->assertEquals('4th', $st3->year_level);
        $this->assertEmpty($st3->section, 'Old section must be cleared');
        $this->assertTrue((bool) $st3->needs_section_confirmation);

        // Check promotion batch log
        $batch = StudentPromotionBatch::latest()->first();
        $this->assertNotNull($batch);
        $this->assertEquals($this->admin->id, $batch->admin_id);
        $this->assertEquals(3, $batch->rows_applied);
        $this->assertEquals(2, $batch->rows_skipped);

        // Check YearLevelAuditLogs
        $auditCount = YearLevelAuditLog::where('action', 'csv_promotion')->count();
        $this->assertEquals(3, $auditCount);
    }

    public function test_reuploading_same_file_does_not_cause_duplicate_or_conflicting_changes(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
        ]);

        $csvContent = "student_number,year_level\n2023-0001,2nd\n";
        $file1 = UploadedFile::fake()->createWithContent('promo.csv', $csvContent);

        // First promotion
        $this->actingAs($this->admin)->post('/manage/promotions/upload', ['csv_file' => $file1]);
        $this->actingAs($this->admin)->post('/manage/promotions/confirm');

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
        $this->assertEmpty($student->section);

        // Second promotion with same file
        $file2 = UploadedFile::fake()->createWithContent('promo.csv', $csvContent);
        $this->actingAs($this->admin)->post('/manage/promotions/upload', ['csv_file' => $file2]);
        $this->actingAs($this->admin)->post('/manage/promotions/confirm');

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
        $this->assertEmpty($student->section);
        $this->assertTrue((bool) $student->needs_section_confirmation);

        // No new user created
        $this->assertEquals(1, User::where('student_id', '2023-0001')->count());
    }

    public function test_promoted_student_is_blocked_by_middleware_until_section_chosen(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Attempting to access dashboard is blocked and redirected to /confirm-section
        $response = $this->actingAs($student)->get('/dashboard');
        $response->assertRedirect('/confirm-section');

        // Attempting to access announcements is blocked
        $response = $this->actingAs($student)->get('/announcements');
        $response->assertRedirect('/confirm-section');

        // Attempting to access inventory is blocked
        $response = $this->actingAs($student)->get('/inventory');
        $response->assertRedirect('/confirm-section');

        // Accessing /confirm-section succeeds and displays the prompt
        $confirmPage = $this->actingAs($student)->get('/confirm-section');
        $confirmPage->assertStatus(200);
        $confirmPage->assertSee("You've been promoted to", false);
        $confirmPage->assertSee('2nd Year');
        $confirmPage->assertSee('Select Your Section');

        // Promoted student can still log out safely
        $logoutResponse = $this->actingAs($student)->post('/logout');
        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_section_submission_is_rejected(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
        ]);

        // Submit section not in valid options for 2nd year (e.g. 4-1 or random string)
        $response = $this->actingAs($student)->post('/confirm-section', [
            'section' => '4-99',
        ]);

        $response->assertSessionHasErrors('section');

        $student->refresh();
        $this->assertEmpty($student->section);
        $this->assertTrue((bool) $student->needs_section_confirmation);
    }

    public function test_valid_section_submission_saves_section_clears_flag_and_allows_portal_access(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-0001',
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
        ]);

        // Submit valid section for 2nd year (e.g. '2-1')
        $response = $this->actingAs($student)->post('/confirm-section', [
            'section' => '2-1',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('2-1', $student->section);
        $this->assertFalse((bool) $student->needs_section_confirmation);

        // Check YearLevelAuditLog
        $auditLog = YearLevelAuditLog::where('user_id', $student->id)
            ->where('action', 'student_section_confirmation')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('2-1', $auditLog->new_section);

        // Student can now visit dashboard without being redirected
        $dashResponse = $this->actingAs($student)->get('/dashboard');
        $dashResponse->assertStatus(200);

        // Student visiting /confirm-section is now redirected to dashboard since confirmation is complete
        $directConfirm = $this->actingAs($student)->get('/confirm-section');
        $directConfirm->assertRedirect('/dashboard');
    }

    public function test_csv_promotion_discards_unfinished_drafts(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023-9999',
            'first_name' => 'Draft',
            'last_name' => 'Tester',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
        ]);

        $draft = \App\Models\InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2026-2027',
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        session()->put('promotion_preview', [
            'file_name' => 'test_drafts.csv',
            'valid_rows' => [
                [
                    'row_number' => 2,
                    'user_id' => $student->id,
                    'student_number' => '2023-9999',
                    'student_name' => 'Tester, Draft',
                    'program' => 'BSIT',
                    'current_year' => '1st',
                    'new_year' => '2nd',
                ],
            ],
            'invalid_rows' => [],
        ]);

        $response = $this->actingAs($this->admin)->post('/manage/promotions/confirm');
        $response->assertRedirect('/manage/promotions');

        $this->assertDatabaseMissing('inventory_submissions', ['id' => $draft->id]);
    }
}
