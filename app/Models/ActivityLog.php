<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Prevent mass assignment of protected log fields from request payloads.
     * Records must be written exclusively through ActivityLogService.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Activity logs are append-only and cannot be modified.');
        });

        static::deleting(function () {
            throw new \LogicException('Activity logs are append-only and cannot be deleted.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->actor_role) {
            'system_admin' => 'System Admin',
            'guidance_counselor' => 'Guidance Counselor',
            'student' => 'Student',
            'system' => 'System',
            'guest' => 'Guest',
            default => Str::title(str_replace('_', ' ', (string) $this->actor_role)),
        };
    }

    /**
     * Single source of truth for all supported activity action slugs and their human-readable labels.
     *
     * @var array<string, string>
     */
    public const ACTION_LABELS = [
        'auth.login' => 'Logged In',
        'auth.login_failed' => 'Failed Sign-In Attempt',
        'auth.logout' => 'Logged Out',
        'auth.registered' => 'Student Registered',
        'auth.password_updated' => 'Password Updated',
        'auth.password_reset' => 'Password Reset',
        'account.created' => 'Account Created',
        'account.updated' => 'Account Updated',
        'account.deactivated' => 'Account Deactivated',
        'account.restored' => 'Account Restored',
        'account.password_reset_sent' => 'Password Reset Link Sent',
        'student.profile_updated' => 'Updated Contact or Profile Info',
        'student.section_confirmed' => 'Section Confirmed',
        'student.archived' => 'Archived Student Record',
        'student.restored' => 'Restored Student Record',
        'student.year_level_overridden' => 'Updated Student Year Level',
        'student.bulk_promoted' => 'Promoted Students (Bulk)',
        'student.academic_year_promoted' => 'Promoted Students for New Academic Year',
        'inventory.submitted' => 'Submitted Student Inventory',
        'inventory.reviewed' => 'Viewed Student Inventory Record',
        'inventory.reassessed' => 'Re-Checked Response Quality',
        'inventory.exported_pdf' => 'Downloaded Student Inventory Report',
        'flag.reviewed' => 'Reviewed Flagged Response',
        'flag.unreviewed' => 'Unmarked Flagged Response',
        'flag_settings.updated' => 'Updated Flag Thresholds',
        'flag_settings.reset' => 'Reset Flag Thresholds',
        'announcement.created' => 'Created Announcement',
        'announcement.updated' => 'Updated Announcement',
        'announcement.published' => 'Published Announcement',
        'announcement.unpublished' => 'Unpublished Announcement',
        'announcement.deleted' => 'Deleted Draft Announcement',
        'question_bank.category_created' => 'Created Assessment Category',
        'question_bank.category_updated' => 'Updated Assessment Category',
        'question_bank.category_deleted' => 'Deleted Assessment Category',
        'question_bank.assignment_added' => 'Assigned Category to Year Level',
        'question_bank.assignment_removed' => 'Removed Category from Year Level',
        'question_bank.assignments_synced' => 'Updated Category Year Levels',
        'question_bank.item_created' => 'Added Assessment Question',
        'question_bank.item_updated' => 'Updated Assessment Question',
        'question_bank.item_deleted' => 'Deleted Assessment Question',
        'question_bank.range_created' => 'Added Scoring Range',
        'question_bank.range_updated' => 'Updated Scoring Range',
        'question_bank.range_deleted' => 'Deleted Scoring Range',
        'institution.department_created' => 'Created School',
        'institution.department_updated' => 'Updated School',
        'institution.department_archived' => 'Archived School',
        'institution.department_restored' => 'Restored School',
        'institution.program_created' => 'Created Program',
        'institution.program_updated' => 'Updated Program',
        'institution.program_archived' => 'Archived Program',
        'institution.program_restored' => 'Restored Program',
        'institution.academic_year_created' => 'Created Academic Year',
        'institution.academic_year_updated' => 'Updated Academic Year',
        'institution.academic_year_archived' => 'Archived Academic Year',
        'institution.academic_year_restored' => 'Restored Academic Year',
        'institution.academic_year_activated' => 'Changed Active Academic Year',
        'schedule.created' => 'Created Assessment Schedule',
        'schedule.updated' => 'Updated Assessment Schedule',
        'report.analytics_exported' => 'Downloaded Analytics Summary Report',
    ];

    public function getActionLabelAttribute(): string
    {
        return static::actionLabelFor((string) $this->action);
    }

    public static function actionLabelFor(string $action): string
    {
        if (isset(static::ACTION_LABELS[$action])) {
            return static::ACTION_LABELS[$action];
        }

        return Str::title(str_replace(['.', '_'], ' ', $action));
    }

    /**
     * Build the complete, deduplicated, alphabetically sorted list of action filter options
     * combining all supported system actions and any distinct historical actions in the database.
     *
     * @return array<string, string> [action_slug => human_readable_label]
     */
    public static function filterActionOptions(): array
    {
        $options = static::ACTION_LABELS;

        $recordedActions = static::query()
            ->select('action')
            ->distinct()
            ->pluck('action');

        foreach ($recordedActions as $slug) {
            $slug = (string) $slug;
            if ($slug !== '' && ! isset($options[$slug])) {
                $options[$slug] = static::actionLabelFor($slug);
            }
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public function getSubjectDisplayAttribute(): ?string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        try {
            return match ($this->subject_type) {
                User::class => ($u = User::find($this->subject_id))
                    ? trim("{$u->first_name} {$u->last_name}")
                    : 'User Account (No longer available)',
                InventorySubmission::class => ($s = InventorySubmission::with('user')->find($this->subject_id))
                    ? (($s->user ? trim("{$s->user->first_name} {$s->user->last_name}") : 'Student') . " (A.Y. {$s->academic_year})")
                    : 'Student Inventory Record',
                InventoryFlag::class => ($f = InventoryFlag::with('submission.user')->find($this->subject_id))
                    ? ($f->submission?->user ? trim("{$f->submission->user->first_name} {$f->submission->user->last_name}") . ' — Flagged Response' : 'Flagged Response')
                    : 'Flagged Response',
                Announcement::class => Announcement::find($this->subject_id)?->title ?? 'Announcement',
                QuestionCategory::class => QuestionCategory::find($this->subject_id)?->label() ?? 'Assessment Category',
                QuestionItem::class => ($qi = QuestionItem::with('category')->find($this->subject_id))
                    ? ('Question #' . $qi->item_number . ($qi->category ? ' (' . $qi->category->label() . ')' : ''))
                    : 'Assessment Question',
                InterpretationRange::class => InterpretationRange::find($this->subject_id)?->label ?? 'Scoring Range',
                Department::class => Department::find($this->subject_id)?->name ?? 'School',
                Program::class => Program::find($this->subject_id)?->name ?? 'Program',
                AcademicYear::class => AcademicYear::find($this->subject_id)?->label ?? 'Academic Year',
                AssessmentSchedule::class => ($as = AssessmentSchedule::find($this->subject_id))
                    ? "{$as->year_level} Year Schedule"
                    : 'Assessment Schedule',
                default => 'System Record',
            };
        } catch (\Throwable $e) {
            return 'System Record';
        }
    }

    /**
     * Format recorded before/after field changes into plain-language labels and values.
     *
     * @return array<int, array{label: string, before: string, after: string}>
     */
    public function formattedChanges(): array
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $skipKeys = [
            'academic_year_id',
            'department_id',
            'program_id',
            'question_category_id',
            'inventory_submission_id',
            'submission_id',
            'flag_id',
            'report_type',
        ];

        $labelMap = [
            'is_active' => 'Account Status',
            'archived_at' => 'Archive Status',
            'year_level' => 'Year Level',
            'year_levels' => 'Assigned Year Levels',
            'section' => 'Section',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'contact_number' => 'Contact Number',
            'address_line1' => 'Street Address',
            'city' => 'City',
            'province' => 'Province',
            'profile_picture' => 'Profile Picture',
            'role' => 'Account Role',
            'department_ids' => 'Assigned Schools',
            'name' => 'Name',
            'display_name' => 'Display Name',
            'code' => 'Program Code',
            'label' => 'Label',
            'is_current' => 'Active Academic Year',
            'is_reviewed' => 'Review Status',
            'flag_type' => 'Flag Type',
            'title' => 'Title',
            'status' => 'Status',
            'published_at' => 'Published Date',
            'expires_at' => 'Expiration Date',
            'submitted_at' => 'Submitted Date',
            'academic_year' => 'Academic Year',
            'open_date' => 'Opening Date',
            'open_time' => 'Opening Time',
            'close_date' => 'Closing Date',
            'close_time' => 'Closing Time',
            'min_score' => 'Minimum Score',
            'max_score' => 'Maximum Score',
            'color_tag' => 'Severity Color',
            'subscale_tag' => 'Subscale',
            'prompt' => 'Question Text',
            'item_number' => 'Question Number',
            'scale_type' => 'Scale Type',
            'display_order' => 'Display Order',
            'affected_count' => 'Students Affected',
            'promoted_count' => 'Students Promoted',
            'manual_section_updates' => 'Sections Needing Manual Update',
            'identifier' => 'Sign-In Identifier',
            'reason' => 'Reason',
            'programs' => 'Programs',
            'date_from' => 'From Date',
            'date_to' => 'To Date',
        ];

        $rows = [];
        foreach ($keys as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }

            $oldVal = array_key_exists($key, $old) ? $old[$key] : null;
            $newVal = array_key_exists($key, $new) ? $new[$key] : null;

            $rows[] = [
                'label' => $labelMap[$key] ?? Str::title(str_replace('_', ' ', (string) $key)),
                'before' => $this->formatFieldValue((string) $key, $oldVal),
                'after' => $this->formatFieldValue((string) $key, $newVal),
            ];
        }

        return $rows;
    }

    private function formatFieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($key === 'is_active') {
            return $value ? 'Active' : 'Deactivated';
        }

        if ($key === 'is_reviewed') {
            return $value ? 'Reviewed' : 'Unreviewed';
        }

        if ($key === 'archived_at') {
            return 'Archived';
        }

        if ($key === 'role') {
            return match ($value) {
                'system_admin' => 'System Admin',
                'guidance_counselor' => 'Guidance Counselor',
                'student' => 'Student',
                default => Str::title(str_replace('_', ' ', (string) $value)),
            };
        }

        if ($key === 'reason') {
            return match ($value) {
                'invalid_credentials' => 'Incorrect email, student ID, or password',
                'account_deactivated' => 'Account is deactivated',
                default => Str::title(str_replace('_', ' ', (string) $value)),
            };
        }

        if ($key === 'department_ids' && is_array($value)) {
            $names = Department::whereIn('id', $value)->pluck('name')->all();
            return ! empty($names) ? implode(', ', $names) : count($value) . ' school(s)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $flat = [];
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $flat[] = (is_string($k) ? Str::title(str_replace('_', ' ', $k)) . ': ' : '') . implode(', ', array_map('strval', $v));
                } else {
                    $flat[] = (is_string($k) ? Str::title(str_replace('_', ' ', $k)) . ': ' : '') . (string) $v;
                }
            }
            return ! empty($flat) ? implode('; ', $flat) : '—';
        }

        return (string) $value;
    }
}

