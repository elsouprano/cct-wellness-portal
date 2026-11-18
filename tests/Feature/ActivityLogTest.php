<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\InterpretationRange;
use App\Models\InterpretationRangeAuditLog;
use App\Models\InventoryFlag;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $counselor;
    private User $student;
    private Department $department;
    private Program $program;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => 'School of Computer Studies']);
        $this->program = Program::create([
            'department_id' => $this->department->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->admin = User::factory()->create([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'role' => 'system_admin',
            'is_active' => true,
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
        ]);

        $this->counselor = User::factory()->create([
            'first_name' => 'Grace',
            'last_name' => 'Counselor',
            'role' => 'guidance_counselor',
            'department_id' => $this->department->id,
            'is_active' => true,
            'email' => 'grace@citycollegeoftagaytay.edu.ph',
            'email_verified_at' => now(),
        ]);
        $this->counselor->assignedDepartments()->sync([$this->department->id]);

        $this->student = User::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'role' => 'student',
            'department_id' => $this->department->id,
            'program_id' => $this->program->id,
            'year_level' => '3rd',
            'section' => '3-1',
            'student_id' => '2024-0001',
            'is_active' => true,
            'email' => 'juan@citycollegeoftagaytay.edu.ph',
            'password' => Hash::make('StudentPass123!'),
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);
    }

    public function test_authentication_events_are_logged_without_passwords(): void
    {
        // Failed login
        $this->post('/login', [
            'identifier' => 'admin@citycollegeoftagaytay.edu.ph',
            'password' => 'WrongPassword999!',
        ]);

        $failedLog = ActivityLog::where('action', 'auth.login_failed')->first();
        $this->assertNotNull($failedLog);
        $this->assertEquals('Authentication', $failedLog->module);
        $this->assertEquals('system', $failedLog->actor_role);
        $this->assertStringNotContainsString('WrongPassword999!', json_encode($failedLog->toArray()));

        // Successful login
        $this->post('/login', [
            'identifier' => 'admin@citycollegeoftagaytay.edu.ph',
            'password' => 'SecretPass123!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $loginLog = ActivityLog::where('action', 'auth.login')->where('actor_id', $this->admin->id)->first();
        $this->assertNotNull($loginLog);
        $this->assertEquals('system_admin', $loginLog->actor_role);
        $this->assertStringNotContainsString('SecretPass123!', json_encode($loginLog->toArray()));

        // Logout
        $this->post('/logout');
        $logoutLog = ActivityLog::where('action', 'auth.logout')->where('actor_id', $this->admin->id)->first();
        $this->assertNotNull($logoutLog);
    }

    public function test_account_and_student_management_events_are_logged(): void
    {
        // Account create
        $this->actingAs($this->admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@citycollegeoftagaytay.edu.ph',
            'department_ids' => [$this->department->id],
        ])->assertRedirect(route('manage.accounts.index'));

        $createdCounselor = User::where('email', 'maria.santos@citycollegeoftagaytay.edu.ph')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'account.created',
            'module' => 'Account Management',
            'actor_id' => $this->admin->id,
            'subject_id' => $createdCounselor->id,
        ]);

        // Account deactivate
        $this->actingAs($this->admin)
            ->patch(route('manage.accounts.toggle-status', $createdCounselor))
            ->assertRedirect(route('manage.accounts.index'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'account.deactivated',
            'subject_id' => $createdCounselor->id,
        ]);

        // Student archive & restore
        $this->actingAs($this->admin)
            ->patch(route('staff.students.archive', $this->student))
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'student.archived',
            'module' => 'Student Management',
            'subject_id' => $this->student->id,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('staff.students.unarchive', $this->student))
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'student.restored',
            'module' => 'Student Management',
            'subject_id' => $this->student->id,
        ]);
    }

    public function test_inventory_review_flag_and_report_export_do_not_leak_answers(): void
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_given_at' => now(),
            'signature_type' => 'typed',
            'signature_data' => 'SECRET_SIGNATURE_DATA',
        ]);

        $flag = InventoryFlag::create([
            'inventory_submission_id' => $submission->id,
            'flag_type' => 'speed',
            'description' => 'Fast completion',
            'is_reviewed' => false,
        ]);

        // Staff reviews submission
        $this->actingAs($this->counselor)
            ->get(route('staff.inventory.show', $submission))
            ->assertOk();

        $reviewLog = ActivityLog::where('action', 'inventory.reviewed')->first();
        $this->assertNotNull($reviewLog);
        $this->assertEquals('Inventory Management', $reviewLog->module);
        $this->assertStringNotContainsString('SECRET_SIGNATURE_DATA', json_encode($reviewLog->toArray()));

        // Staff reviews flag (reviewer_notes must not be logged)
        $this->actingAs($this->counselor)
            ->patch(route('staff.flags.review', $flag), ['reviewer_notes' => 'CONFIDENTIAL_COUNSELOR_NOTE'])
            ->assertRedirect();

        $flagLog = ActivityLog::where('action', 'flag.reviewed')->first();
        $this->assertNotNull($flagLog);
        $this->assertStringNotContainsString('CONFIDENTIAL_COUNSELOR_NOTE', json_encode($flagLog->toArray()));

        // Staff exports analytics CSV
        $this->actingAs($this->counselor)
            ->get(route('analytics.export'))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'report.analytics_exported',
            'module' => 'Reports',
            'actor_id' => $this->counselor->id,
        ]);
    }

    public function test_announcements_question_bank_and_institution_actions_are_logged(): void
    {
        // Announcement publish & unpublish
        $this->actingAs($this->admin)->post(route('announcements.store'), [
            'title' => 'Wellness Orientation',
            'body' => '<p>Schedule details</p>',
            'status' => 'publish_now',
        ])->assertRedirect(route('announcements.index'));

        $announcement = Announcement::firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'announcement.published',
            'module' => 'Announcements',
            'subject_id' => $announcement->id,
        ]);

        $this->actingAs($this->admin)->patch(route('announcements.unpublish', $announcement));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'announcement.unpublished',
            'subject_id' => $announcement->id,
        ]);

        // Question Bank category & item
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '3rd',
            'name' => 'dass21',
            'display_name' => 'Depression Anxiety Stress Scales (DASS-21)',
            'display_order' => 1,
            'scale_type' => 'likert',
            'scale_min' => 0,
            'scale_max' => 3,
        ]);

        $this->actingAs($this->admin)->post(route('question-bank.items.store', $category), [
            'prompt' => 'I felt down-hearted and blue.',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'question_bank.item_created',
            'module' => 'Question Bank',
        ]);

        // Institution department update
        $this->actingAs($this->admin)->put(route('institution.departments.update', $this->department), [
            'name' => 'School of Computing Studies',
        ])->assertRedirect(route('institution.index'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'institution.department_updated',
            'module' => 'Institution Management',
            'subject_id' => $this->department->id,
        ]);
    }

    public function test_authorization_blocks_counselors_students_and_guests_from_activity_logs(): void
    {
        $log = app(ActivityLogService::class)->log(
            action: 'account.created',
            module: 'Account Management',
            description: 'Test entry',
            actor: $this->admin,
        );

        // Guest redirected
        $this->get(route('activity-logs.index'))->assertRedirect(route('login'));

        // Student blocked (403)
        $this->actingAs($this->student)->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($this->student)->get(route('activity-logs.show', $log))->assertForbidden();

        // Counselor blocked (403) and does not see sidebar link
        $this->actingAs($this->counselor)->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($this->counselor)->get(route('activity-logs.show', $log))->assertForbidden();
        $this->actingAs($this->counselor)->get(route('dashboard'))->assertDontSee('Activity Logs');

        // System Admin allowed and sees sidebar link
        $this->actingAs($this->admin)->get(route('activity-logs.index'))->assertOk()->assertSee('Test entry');
        $this->actingAs($this->admin)->get(route('activity-logs.show', $log))->assertOk()->assertSee('Test entry');
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('Activity Logs');
    }

    public function test_activity_logs_are_append_only_xss_safe_and_idempotently_synced(): void
    {
        $service = app(ActivityLogService::class);
        $xssLog = $service->log(
            action: 'announcement.created',
            module: 'Announcements',
            description: '<script>alert("xss")</script> Malicious Title',
            newValues: [
                'password' => 'should_be_stripped',
                'remember_token' => 'strip_me',
                'session_id' => 'strip_session',
                'responses' => ['q1' => 3],
                'title' => 'Safe Title',
            ],
            actor: $this->admin,
        );

        $this->assertNull($xssLog->new_values['password'] ?? null);
        $this->assertNull($xssLog->new_values['remember_token'] ?? null);
        $this->assertNull($xssLog->new_values['session_id'] ?? null);
        $this->assertNull($xssLog->new_values['responses'] ?? null);
        $this->assertEquals('Safe Title', $xssLog->new_values['title']);

        // XSS is escaped in HTML output
        $indexRes = $this->actingAs($this->admin)->get(route('activity-logs.index'));
        $indexRes->assertOk();
        $indexRes->assertDontSee('<script>alert("xss")</script>', false);
        $indexRes->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);

        // Append-only enforcement
        $this->expectException(\LogicException::class);
        $xssLog->delete();
    }

    public function test_historical_specialized_audit_logs_sync_idempotently(): void
    {
        $ylAudit = YearLevelAuditLog::create([
            'user_id' => $this->student->id,
            'actor_id' => $this->admin->id,
            'action' => 'individual_override',
            'old_year_level' => '2nd',
            'new_year_level' => '3rd',
            'old_section' => '2-1',
            'new_section' => '3-1',
        ]);

        $rangeAudit = InterpretationRangeAuditLog::create([
            'actor_id' => $this->admin->id,
            'action' => 'edited_official_default',
            'details' => [
                'question_category_id' => 1,
                'range_id' => 99,
                'old_values' => ['min_score' => 0, 'max_score' => 9],
                'new_values' => ['min_score' => 0, 'max_score' => 10],
            ],
        ]);

        $service = app(ActivityLogService::class);
        $firstCount = $service->syncHistoricalAuditLogs(force: true);
        $secondCount = $service->syncHistoricalAuditLogs(force: true);

        $this->assertEquals(2, $firstCount);
        $this->assertEquals(0, $secondCount);
        $this->assertEquals(1, ActivityLog::where('source_type', YearLevelAuditLog::class)->where('source_id', $ylAudit->id)->count());
        $this->assertEquals(1, ActivityLog::where('source_type', InterpretationRangeAuditLog::class)->where('source_id', $rangeAudit->id)->count());
    }

    public function test_search_filter_pagination_and_no_edit_delete_routes(): void
    {
        $service = app(ActivityLogService::class);
        for ($i = 1; $i <= 18; $i++) {
            $service->log(
                action: 'student.profile_updated',
                module: 'Student Management',
                description: "Profile update #{$i}",
                actor: $this->student,
            );
        }

        // Default 15 per page
        $res = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'role' => 'student',
            'module' => 'Student Management',
            'search' => 'Profile update',
            'sort' => 'newest',
        ]));
        $res->assertOk();
        $res->assertSee('Showing');
        $res->assertSee('18');

        // Validation rejects invalid role/per_page
        $this->actingAs($this->admin)
            ->get(route('activity-logs.index', ['role' => 'hacker', 'per_page' => 999]))
            ->assertSessionHasErrors(['role', 'per_page']);

        // No edit/delete endpoints
        $log = ActivityLog::firstOrFail();
        $this->actingAs($this->admin)->delete('/manage/activity-logs/' . $log->id)->assertStatus(405);
        $this->actingAs($this->admin)->put('/manage/activity-logs/' . $log->id)->assertStatus(405);
    }

    public function test_human_friendly_ui_hides_technical_metadata_and_handles_missing_subjects(): void
    {
        $log = app(ActivityLogService::class)->withIpTest ?? null;
        $record = $this->actingAs($this->admin)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.10.55', 'HTTP_USER_AGENT' => 'Mozilla/5.0 TestAgent/9.9'])
            ->patch(route('staff.students.archive', $this->student));
        $record->assertRedirect();

        $archivedLog = ActivityLog::where('action', 'student.archived')->latest('id')->firstOrFail();

        // IP and user agent are still recorded internally
        $this->assertEquals('192.168.10.55', $archivedLog->ip_address);
        $this->assertEquals('Mozilla/5.0 TestAgent/9.9', $archivedLog->user_agent);

        // Index page hides IP address, raw action slug, and internal user IDs
        $indexRes = $this->actingAs($this->admin)->get(route('activity-logs.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Archived Student Record');
        $indexRes->assertDontSee('192.168.10.55');
        $indexRes->assertDontSee('ID #' . $this->admin->id);

        // Details page shows plain-language labels and hides raw IP/User-Agent/slug
        $showRes = $this->actingAs($this->admin)->get(route('activity-logs.show', $archivedLog));
        $showRes->assertOk();
        $showRes->assertSee('Juan Dela Cruz');
        $showRes->assertSee('Account Status');
        $showRes->assertSee('Deactivated');
        $showRes->assertDontSee('192.168.10.55');
        $showRes->assertDontSee('Mozilla/5.0 TestAgent/9.9');
        $showRes->assertDontSee('student.archived');

        // Missing/deleted subject relationship is handled gracefully
        $orphanLog = app(ActivityLogService::class)->log(
            action: 'announcement.updated',
            module: 'Announcements',
            description: 'Updated an announcement.',
            actor: $this->admin,
            subjectTypeOverride: Announcement::class,
            subjectIdOverride: 999999,
        );
        $orphanRes = $this->actingAs($this->admin)->get(route('activity-logs.show', $orphanLog));
        $orphanRes->assertOk();
        $orphanRes->assertSee('Announcement');
    }

    public function test_action_type_filter_includes_all_supported_actions_and_filters_accurately(): void
    {
        $service = app(ActivityLogService::class);

        // Record an unknown legacy action slug as well
        $service->log(
            action: 'custom_module.legacy_action',
            module: 'System',
            description: 'Legacy system event.',
            actor: $this->admin,
        );

        // Record 12 matching inventory.reviewed logs and 3 non-matching auth.login logs
        for ($i = 1; $i <= 12; $i++) {
            $service->log(
                action: 'inventory.reviewed',
                module: 'Inventory Management',
                description: "Viewed student inventory record #{$i} for Juan Dela Cruz.",
                actor: $this->counselor,
            );
        }
        for ($i = 1; $i <= 3; $i++) {
            $service->log(
                action: 'auth.login',
                module: 'Authentication',
                description: 'User logged in.',
                actor: $this->admin,
            );
        }

        // 1-4: All supported actions + unknown fallback appear, are human-readable, and have no duplicates
        $options = ActivityLog::filterActionOptions();
        $this->assertGreaterThanOrEqual(count(ActivityLog::ACTION_LABELS), count($options));
        $this->assertEquals(count($options), count(array_unique(array_keys($options))));
        $this->assertEquals(count($options), count(array_unique(array_values($options))));
        $this->assertArrayHasKey('auth.login', $options);
        $this->assertArrayHasKey('inventory.submitted', $options);
        $this->assertArrayHasKey('announcement.published', $options);
        $this->assertArrayHasKey('custom_module.legacy_action', $options);
        $this->assertEquals('Custom Module Legacy Action', $options['custom_module.legacy_action']);

        // 5-7: Selecting action filters records, combines with other filters, and persists across pagination
        $today = now()->format('Y-m-d');
        $filteredRes = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'action' => 'inventory.reviewed',
            'module' => 'Inventory Management',
            'role' => 'guidance_counselor',
            'search' => 'Juan Dela Cruz',
            'date_from' => $today,
            'date_to' => $today,
            'sort' => 'newest',
            'per_page' => 10,
        ]));
        $filteredRes->assertOk();
        $filteredRes->assertSee('All Actions');
        $filteredRes->assertSee('Viewed Student Inventory Record');
        $filteredRes->assertDontSee('Legacy system event.');

        $logsPaginator = $filteredRes->viewData('logs');
        $this->assertEquals(12, $logsPaginator->total());
        $this->assertEquals(10, $logsPaginator->count());

        // Pagination URL preserves selected action filter
        $page2Url = $logsPaginator->url(2);
        $this->assertStringContainsString('action=inventory.reviewed', $page2Url);
        $page2Res = $this->actingAs($this->admin)->get($page2Url);
        $page2Res->assertOk();
        $this->assertEquals(2, $page2Res->viewData('logs')->count());

        // 8: Unknown/invalid action slug in query string is handled safely without error
        $unknownRes = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'action' => 'nonexistent.action_slug',
        ]));
        $unknownRes->assertOk();
        $unknownRes->assertSee('No activity records found');
    }
}

