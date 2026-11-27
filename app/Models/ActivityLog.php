<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Roles permitted to appear in Activity Logs and My Activity.
     *
     * @var array<int, string>
     */
    public const STAFF_ROLES = [
        'system_admin',
        'guidance_counselor',
    ];

    /**
     * Canonical plain-language areas displayed in the UI and filter bar.
     *
     * @var array<int, string>
     */
    public const AREAS = [
        'Announcements',
        'Schedules',
        'Question Bank',
        'Students',
        'Accounts',
        'Institution',
        'Flagged Responses',
        'Settings',
    ];

    /**
     * Legacy/non-staff action slugs excluded from staff activity filter options.
     *
     * @var array<int, string>
     */
    public const EXCLUDED_ACTIONS = [
        'auth.login_failed',
        'auth.registered',
        'student.section_confirmed',
        'inventory.submitted',
    ];

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

    /**
     * Scope queries strictly to staff-performed entries (system admins & guidance counselors).
     */
    public function scopeStaffOnly(Builder $query): Builder
    {
        return $query->whereIn('actor_role', self::STAFF_ROLES);
    }

    /**
     * Filter entries by canonical plain-language area (with legacy module fallback).
     */
    public function scopeInArea(Builder $query, string $area): Builder
    {
        $normalized = trim($area);
        if ($normalized === '') {
            return $query;
        }

        $legacyToArea = [
            'Assessment Schedules' => 'Schedules',
            'Student Management' => 'Students',
            'Inventory Management' => 'Students',
            'Reports' => 'Students',
            'Account Management' => 'Accounts',
            'Institution Management' => 'Institution',
            'Flag Settings' => 'Settings',
            'Authentication' => 'Settings',
            'System' => 'Settings',
        ];

        $canonical = $legacyToArea[$normalized] ?? $normalized;

        return match ($canonical) {
            'Announcements' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'announcement.%')
                  ->orWhere('module', 'Announcements');
            }),
            'Schedules' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'schedule.%')
                  ->orWhereIn('module', ['Schedules', 'Assessment Schedules']);
            }),
            'Question Bank' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'question_bank.%')
                  ->orWhere('module', 'Question Bank');
            }),
            'Flagged Responses' => $query->where(function (Builder $q) {
                $q->whereIn('action', ['flag.reviewed', 'flag.unreviewed'])
                  ->orWhere('module', 'Flagged Responses');
            }),
            'Accounts' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'account.%')
                  ->orWhereIn('module', ['Accounts', 'Account Management']);
            }),
            'Institution' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'institution.%')
                  ->orWhereIn('module', ['Institution', 'Institution Management']);
            }),
            'Settings' => $query->where(function (Builder $q) {
                $q->where('action', 'like', 'flag_settings.%')
                  ->orWhere('action', 'like', 'auth.%')
                  ->orWhere('action', 'student.profile_updated')
                  ->orWhereIn('module', ['Settings', 'Flag Settings', 'Authentication', 'System']);
            }),
            'Students' => $query->where(function (Builder $q) {
                $q->where(function (Builder $sub) {
                    $sub->where('action', 'like', 'student.%')
                        ->where('action', '!=', 'student.profile_updated');
                })
                ->orWhere('action', 'like', 'inventory.%')
                ->orWhere('action', 'like', 'report.%')
                ->orWhere(function (Builder $sub) {
                    $sub->whereIn('module', ['Students', 'Student Management', 'Inventory Management', 'Reports'])
                        ->whereNotIn('action', ['flag.reviewed', 'flag.unreviewed', 'student.profile_updated']);
                });
            }),
            default => $query->where('module', $normalized),
        };
    }

    public function getActorDisplayNameAttribute(): string
    {
        if ($this->actor) {
            $name = trim("{$this->actor->first_name} {$this->actor->last_name}");
            if ($name !== '') {
                return $name;
            }
        }

        return trim((string) ($this->actor_name ?: 'Staff Member'));
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->actor_role) {
            'system_admin' => 'System Administrator',
            'guidance_counselor' => 'Guidance Counselor',
            default => Str::title(str_replace('_', ' ', (string) $this->actor_role)),
        };
    }

    public function getAreaLabelAttribute(): string
    {
        $action = (string) $this->action;

        if (str_starts_with($action, 'announcement.')) {
            return 'Announcements';
        }
        if (str_starts_with($action, 'schedule.')) {
            return 'Schedules';
        }
        if (str_starts_with($action, 'question_bank.')) {
            return 'Question Bank';
        }
        if (in_array($action, ['flag.reviewed', 'flag.unreviewed'], true)) {
            return 'Flagged Responses';
        }
        if (str_starts_with($action, 'flag_settings.') || str_starts_with($action, 'auth.') || $action === 'student.profile_updated') {
            return 'Settings';
        }
        if (str_starts_with($action, 'account.')) {
            return 'Accounts';
        }
        if (str_starts_with($action, 'institution.')) {
            return 'Institution';
        }
        if (str_starts_with($action, 'student.') || str_starts_with($action, 'inventory.') || str_starts_with($action, 'report.')) {
            return 'Students';
        }

        return match ((string) $this->module) {
            'Announcements' => 'Announcements',
            'Schedules', 'Assessment Schedules' => 'Schedules',
            'Question Bank' => 'Question Bank',
            'Students', 'Student Management', 'Inventory Management', 'Reports' => 'Students',
            'Accounts', 'Account Management' => 'Accounts',
            'Institution', 'Institution Management' => 'Institution',
            'Flagged Responses' => 'Flagged Responses',
            default => 'Settings',
        };
    }

    /**
     * Plain-language labels for all supported staff activity actions.
     *
     * @var array<string, string>
     */
    public const ACTION_LABELS = [
        'announcement.created' => 'Saved a draft announcement',
        'announcement.published' => 'Published an announcement',
        'announcement.updated' => 'Edited an announcement',
        'announcement.unpublished' => 'Unpublished an announcement',
        'announcement.deleted' => 'Deleted a draft announcement',
        'schedule.created' => 'Created a schedule',
        'schedule.updated' => 'Updated a schedule',
        'question_bank.category_created' => 'Created a question category',
        'question_bank.category_updated' => 'Updated a question category',
        'question_bank.category_deleted' => 'Removed a question category',
        'question_bank.assignment_added' => 'Assigned a category to a year level',
        'question_bank.assignment_removed' => 'Removed a category from a year level',
        'question_bank.assignments_synced' => 'Updated category year levels',
        'question_bank.item_created' => 'Added a question',
        'question_bank.item_updated' => 'Edited a question',
        'question_bank.item_deleted' => 'Deleted a question',
        'question_bank.range_created' => 'Added a scoring range',
        'question_bank.range_updated' => 'Updated a scoring range',
        'question_bank.range_deleted' => 'Deleted a scoring range',
        'student.archived' => 'Archived a student',
        'student.restored' => 'Restored a student',
        'student.year_level_overridden' => "Changed a student's year level",
        'student.bulk_promoted' => 'Promoted students in bulk',
        'student.academic_year_promoted' => 'Promoted students for new academic year',
        'inventory.reviewed' => 'Viewed a student submission',
        'inventory.reassessed' => 'Re-checked response quality',
        'inventory.exported_pdf' => 'Downloaded a student report',
        'report.analytics_exported' => 'Exported analytics summary',
        'flag.reviewed' => 'Marked a flagged response as reviewed',
        'flag.unreviewed' => 'Marked a flagged response as unreviewed',
        'flag_settings.updated' => 'Updated flag settings',
        'flag_settings.reset' => 'Reset flag settings to defaults',
        'account.created' => 'Created a counselor account',
        'account.updated' => 'Updated a staff account',
        'account.deactivated' => 'Deactivated a staff account',
        'account.restored' => 'Restored a staff account',
        'account.self_deleted' => 'Deleted own account',
        'account.password_reset_sent' => 'Sent a password reset link',
        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'auth.password_updated' => 'Changed own password',
        'auth.password_reset' => 'Reset own password',
        'student.profile_updated' => 'Updated own profile',
        'institution.department_created' => 'Added a school',
        'institution.department_updated' => 'Updated a school',
        'institution.department_archived' => 'Archived a school',
        'institution.department_restored' => 'Restored a school',
        'institution.program_created' => 'Added a program',
        'institution.program_updated' => 'Updated a program',
        'institution.program_archived' => 'Archived a program',
        'institution.program_restored' => 'Restored a program',
        'institution.academic_year_created' => 'Added an academic year',
        'institution.academic_year_updated' => 'Updated an academic year',
        'institution.academic_year_archived' => 'Archived an academic year',
        'institution.academic_year_restored' => 'Restored an academic year',
        'institution.academic_year_activated' => 'Changed active academic year',
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
     * Build the deduplicated, alphabetically sorted list of staff action filter options.
     *
     * @return array<string, string> [action_slug => human_readable_label]
     */
    public static function filterActionOptions(?int $actorId = null): array
    {
        $options = static::ACTION_LABELS;

        $recordedQuery = static::staffOnly()
            ->select('action')
            ->whereNotIn('action', static::EXCLUDED_ACTIONS)
            ->distinct();

        if ($actorId !== null) {
            $recordedQuery->where('actor_id', $actorId);
        }

        foreach ($recordedQuery->pluck('action') as $slug) {
            $slug = (string) $slug;
            if ($slug !== '' && ! isset($options[$slug])) {
                $options[$slug] = static::actionLabelFor($slug);
            }
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * Format the entry's "What" sentence in clear, plain English with zero raw codes, IDs, or ISO timestamps.
     */
    public function getPlainDescriptionAttribute(): string
    {
        $actor = $this->actor_display_name;
        $raw = trim((string) $this->description);

        if ($raw === '') {
            return $actor . ' ' . lcfirst($this->action_label) . '.';
        }

        // Convert ISO date-times (e.g. 2026-11-21 17:23:00 or 2026-11-21 17:23) into human dates/times
        $clean = preg_replace_callback(
            '/\b(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})(?::\d{2})?\b/',
            static function (array $m): string {
                try {
                    return Carbon::createFromFormat('Y-m-d H:i', "{$m[1]} {$m[2]}")->format('M j, Y \a\t g:i A');
                } catch (\Throwable) {
                    return $m[0];
                }
            },
            $raw
        ) ?? $raw;

        // Convert standalone ISO dates (e.g. 2026-11-21, not Academic Year 2026-2027)
        $clean = preg_replace_callback(
            '/\b(\d{4}-\d{2}-\d{2})\b/',
            static function (array $m): string {
                try {
                    return Carbon::createFromFormat('Y-m-d', $m[1])->format('M j, Y');
                } catch (\Throwable) {
                    return $m[0];
                }
            },
            $clean
        ) ?? $clean;

        // Convert standalone 24h times with seconds (e.g. 17:23:00)
        $clean = preg_replace_callback(
            '/\b([01]\d|2[0-3]):([0-5]\d):[0-5]\d\b/',
            static function (array $m): string {
                try {
                    return Carbon::createFromFormat('H:i', "{$m[1]}:{$m[2]}")->format('g:i A');
                } catch (\Throwable) {
                    return $m[0];
                }
            },
            $clean
        ) ?? $clean;

        // Strip raw model class names, internal ID # tokens, or accidental IPv4 addresses
        $clean = preg_replace('/\bApp\\\\Models\\\\[A-Za-z0-9_\\\\]+\b/', 'record', $clean) ?? $clean;
        $clean = preg_replace('/\s*\(ID\s*#?\d+\)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\bID\s*#\d+\b/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', '', $clean) ?? $clean;
        $clean = trim(preg_replace('/\s{2,}/', ' ', $clean) ?? $clean);

        if ($actor !== '' && stripos($clean, $actor) === 0) {
            return $clean;
        }

        if (preg_match('/^(Created|Updated|Deleted|Removed|Added|Edited|Published|Unpublished|Saved|Archived|Restored|Deactivated|Reactivated|Activated|Assigned|Synced|Promoted|Viewed|Opened|Reviewed|Unmarked|Marked|Re-checked|Re-Checked|Reassessed|Downloaded|Exported|Sent|Reset|Changed|Logged|Signed|Set|Renamed)\b/u', $clean, $verbMatch)) {
            $loweredVerb = mb_strtolower($verbMatch[1]);
            $rest = substr($clean, strlen($verbMatch[1]));

            return "{$actor} {$loweredVerb}{$rest}";
        }

        return "{$actor} — {$clean}";
    }

    public function getWhenLabelAttribute(): string
    {
        $dt = $this->created_at;
        if (! $dt) {
            return 'Unknown time';
        }

        if ($dt->isToday()) {
            return 'Today, ' . $dt->format('g:i A');
        }

        if ($dt->isYesterday()) {
            return 'Yesterday, ' . $dt->format('g:i A');
        }

        return $dt->format('M j, Y, g:i A');
    }

    public function getDayGroupLabelAttribute(): string
    {
        $dt = $this->created_at;
        if (! $dt) {
            return 'Earlier';
        }

        if ($dt->isToday()) {
            return 'Today';
        }

        if ($dt->isYesterday()) {
            return 'Yesterday';
        }

        return $dt->format('F j, Y');
    }

    public function hasRecordedChanges(): bool
    {
        return ! empty($this->formattedChanges());
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
                QuestionItem::class => ($qi = QuestionItem::with('questionCategory')->find($this->subject_id))
                    ? ('Question ' . $qi->item_number . ($qi->questionCategory ? ' (' . $qi->questionCategory->label() . ')' : ''))
                    : 'Assessment Question',
                InterpretationRange::class => InterpretationRange::find($this->subject_id)?->label ?? 'Scoring Range',
                Department::class => Department::find($this->subject_id)?->name ?? 'School',
                Program::class => Program::find($this->subject_id)?->name ?? 'Program',
                AcademicYear::class => AcademicYear::find($this->subject_id)?->label ?? 'Academic Year',
                AssessmentSchedule::class => ($as = AssessmentSchedule::with('structuredProgram')->find($this->subject_id))
                    ? ($as->year_level . ' Year ' . ($as->structuredProgram?->code ?? 'All Programs') . ' Schedule')
                    : 'Assessment Schedule',
                default => 'Portal Record',
            };
        } catch (\Throwable) {
            return 'Portal Record';
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
            'department_id',
            'question_category_id',
            'inventory_submission_id',
            'submission_id',
            'flag_id',
            'report_type',
        ];

        $labelMap = [
            'academic_year_id' => 'Academic Year',
            'program_id' => 'Program',
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

            $hasOld = array_key_exists($key, $old);
            $hasNew = array_key_exists($key, $new);
            $oldVal = $hasOld ? $old[$key] : null;
            $newVal = $hasNew ? $new[$key] : null;

            $rows[] = [
                'label' => $labelMap[$key] ?? Str::title(str_replace('_', ' ', (string) $key)),
                'before' => $this->formatFieldValue((string) $key, $oldVal, $hasOld),
                'after' => $this->formatFieldValue((string) $key, $newVal, $hasNew),
            ];
        }

        return $rows;
    }

    private function formatFieldValue(string $key, mixed $value, bool $keyPresent = true): string
    {
        if (! $keyPresent) {
            return '—';
        }

        if ($key === 'program_id') {
            if ($value === null || $value === '') {
                return 'All Programs';
            }

            $prog = Program::find($value);

            return $prog ? ($prog->code ?: $prog->name) : 'Selected Program';
        }

        if ($key === 'academic_year_id') {
            if ($value === null || $value === '') {
                return '—';
            }

            return AcademicYear::find($value)?->label ?? 'Selected Academic Year';
        }

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

        if ($key === 'year_level' && is_string($value)) {
            return str_contains(strtolower($value), 'year') ? $value : "{$value} Year";
        }

        if (in_array($key, ['open_date', 'close_date', 'date_from', 'date_to'], true) && is_string($value)) {
            try {
                return Carbon::parse($value)->format('M j, Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        if (in_array($key, ['open_time', 'close_time'], true) && is_string($value)) {
            try {
                return Carbon::parse($value)->format('g:i A');
            } catch (\Throwable) {
                return $value;
            }
        }

        if (in_array($key, ['published_at', 'expires_at', 'submitted_at'], true) && is_string($value)) {
            try {
                return Carbon::parse($value)->format('M j, Y, g:i A');
            } catch (\Throwable) {
                return $value;
            }
        }

        if ($key === 'role') {
            return match ($value) {
                'system_admin' => 'System Administrator',
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
