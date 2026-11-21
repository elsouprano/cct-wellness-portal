<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\InterpretationRangeAuditLog;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    /**
     * Sensitive keys that must never be persisted in activity logs.
     */
    private const BLOCKED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'password_hash',
        'remember_token',
        'token',
        'api_token',
        'csrf_token',
        '_token',
        'session_id',
        'signature_data',
        'responses',
        'timings',
        'reviewer_notes',
    ];

    /**
     * Record a centralized activity log entry using the authenticated actor (or system).
     *
     * @param array<int, string>|null $allowedFields Optional allowlist applied to oldValues/newValues.
     */
    public function log(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $allowedFields = null,
        ?User $actor = null,
        bool $isSystem = false,
        ?string $sourceType = null,
        ?int $sourceId = null,
        bool $critical = false,
        ?string $subjectTypeOverride = null,
        ?int $subjectIdOverride = null,
    ): ?ActivityLog {
        try {
            if ($sourceType && $sourceId) {
                $exists = ActivityLog::where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->exists();
                if ($exists) {
                    return null;
                }
            }

            $resolvedActor = $isSystem ? null : ($actor ?? Auth::user());

            // Never record routine or account activity logs for student actors.
            if ($resolvedActor !== null && $resolvedActor->role === 'student') {
                return null;
            }

            $actorId = $resolvedActor?->id;
            $actorName = $isSystem
                ? 'System'
                : ($resolvedActor ? trim("{$resolvedActor->first_name} {$resolvedActor->last_name}") : 'Guest');
            $actorRole = $isSystem
                ? 'system'
                : ($resolvedActor?->role ?? 'guest');

            $sanitizedOld = $this->sanitizeValues($oldValues, $allowedFields);
            $sanitizedNew = $this->sanitizeValues($newValues, $allowedFields);

            $request = app()->bound('request') ? request() : null;
            $ipAddress = $request?->ip();
            $userAgent = $request?->userAgent();
            if ($userAgent !== null && mb_strlen($userAgent) > 512) {
                $userAgent = mb_substr($userAgent, 0, 512);
            }

            $log = new ActivityLog();
            $log->actor_id = $actorId;
            $log->actor_name = $actorName ?: ($isSystem ? 'System' : 'User');
            $log->actor_role = $actorRole;
            $log->action = $action;
            $log->module = $module;
            $log->description = $description;
            $log->subject_type = $subject ? get_class($subject) : $subjectTypeOverride;
            $log->subject_id = $subject?->getKey() ?? $subjectIdOverride;
            $log->old_values = $sanitizedOld;
            $log->new_values = $sanitizedNew;
            $log->ip_address = $ipAddress;
            $log->user_agent = $userAgent;
            $log->source_type = $sourceType;
            $log->source_id = $sourceId;
            $log->created_at = now();
            $log->save();

            return $log;
        } catch (\Throwable $e) {
            Log::error('Failed to write activity log', [
                'action' => $action,
                'module' => $module,
                'error' => $e->getMessage(),
            ]);

            if ($critical) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Log a failed login attempt to the dedicated security channel without storing secrets.
     */
    public function logFailedLogin(string $identifier, string $reason = 'invalid_credentials'): ?ActivityLog
    {
        $maskedIdentifier = $this->maskIdentifier($identifier);
        $request = app()->bound('request') ? request() : null;

        Log::channel('security')->warning('Failed authentication attempt', [
            'event' => 'auth.login_failed',
            'identifier' => $maskedIdentifier,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return null;
    }

    /**
     * Filter and sanitize before/after values using an allowlist and strict blocklist.
     *
     * @param array<string, mixed>|null $values
     * @param array<int, string>|null $allowedFields
     * @return array<string, mixed>|null
     */
    public function sanitizeValues(?array $values, ?array $allowedFields = null): ?array
    {
        if (empty($values)) {
            return null;
        }

        $filtered = [];
        foreach ($values as $key => $value) {
            $keyStr = (string) $key;

            if ($this->isBlockedKey($keyStr)) {
                continue;
            }

            if ($allowedFields !== null && ! in_array($keyStr, $allowedFields, true)) {
                continue;
            }

            if (is_array($value)) {
                $nested = $this->sanitizeValues($value, null);
                if ($nested !== null) {
                    $filtered[$keyStr] = $nested;
                }
            } else {
                $filtered[$keyStr] = $value;
            }
        }

        return empty($filtered) ? null : $filtered;
    }

    private function isBlockedKey(string $key): bool
    {
        $lower = strtolower($key);
        if (in_array($lower, self::BLOCKED_KEYS, true)) {
            return true;
        }

        return (bool) preg_match('/password|token|secret|session|signature_data|responses|timings|reviewer_notes/i', $lower);
    }

    private function maskIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return 'unknown';
        }

        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
            return $visible . '***@' . $domain;
        }

        $len = mb_strlen($identifier);
        if ($len <= 3) {
            return str_repeat('*', $len);
        }

        return mb_substr($identifier, 0, 2) . str_repeat('*', $len - 4) . mb_substr($identifier, -2);
    }

    /**
     * Idempotently backfill historical YearLevelAuditLog and InterpretationRangeAuditLog records
     * into activity_logs without creating duplicates or running on every page load.
     */
    public function syncHistoricalAuditLogs(bool $force = false): int
    {
        $cacheKey = 'activity_logs_historical_sync_v1';
        if (! $force && Cache::get($cacheKey)) {
            return 0;
        }

        $synced = 0;

        // 1. InterpretationRangeAuditLog
        InterpretationRangeAuditLog::with('actor')
            ->orderBy('id')
            ->chunkById(100, function ($logs) use (&$synced) {
                $existingIds = ActivityLog::where('source_type', InterpretationRangeAuditLog::class)
                    ->whereIn('source_id', $logs->pluck('id'))
                    ->pluck('source_id')
                    ->all();

                $rows = [];
                foreach ($logs as $log) {
                    if (in_array($log->id, $existingIds, true)) {
                        continue;
                    }

                    $actor = $log->actor;
                    $action = $log->action === 'deleted_official_default'
                        ? 'question_bank.range_deleted'
                        : 'question_bank.range_updated';
                    $details = is_array($log->details) ? $log->details : [];
                    $catId = $details['question_category_id'] ?? null;
                    $catLabel = $catId ? (\App\Models\QuestionCategory::find($catId)?->label() ?? 'assessment category') : 'assessment category';
                    $verb = $log->action === 'deleted_official_default' ? 'Deleted' : 'Updated';

                    $rows[] = [
                        'actor_id' => $actor?->id,
                        'actor_name' => $actor ? trim("{$actor->first_name} {$actor->last_name}") : 'System',
                        'actor_role' => $actor?->role ?? 'system',
                        'action' => $action,
                        'module' => 'Question Bank',
                        'description' => "{$verb} scoring range for {$catLabel}.",
                        'subject_type' => \App\Models\InterpretationRange::class,
                        'subject_id' => $details['range_id'] ?? null,
                        'old_values' => isset($details['old_values']) ? json_encode($this->sanitizeValues($details['old_values'])) : (isset($details['deleted_values']) ? json_encode($this->sanitizeValues($details['deleted_values'])) : null),
                        'new_values' => isset($details['new_values']) ? json_encode($this->sanitizeValues($details['new_values'])) : null,
                        'source_type' => InterpretationRangeAuditLog::class,
                        'source_id' => $log->id,
                        'created_at' => $log->created_at ?? now(),
                    ];
                }

                if (! empty($rows)) {
                    DB::table('activity_logs')->insertOrIgnore($rows);
                    $synced += count($rows);
                }
            });

        // 2. YearLevelAuditLog (skip bulk_promote/academic_year_promotion rows that belong to batches if needed, or import individual ones cleanly)
        YearLevelAuditLog::with(['actor', 'user'])
            ->orderBy('id')
            ->chunkById(200, function ($logs) use (&$synced) {
                $existingIds = ActivityLog::where('source_type', YearLevelAuditLog::class)
                    ->whereIn('source_id', $logs->pluck('id'))
                    ->pluck('source_id')
                    ->all();

                $rows = [];
                foreach ($logs as $log) {
                    if (in_array($log->id, $existingIds, true)) {
                        continue;
                    }

                    $actor = $log->actor;
                    if (in_array($log->action, ['registration', 'student_section_confirmation'], true) || $actor?->role === 'student') {
                        continue;
                    }

                    $student = $log->user;
                    $studentLabel = $student ? trim("{$student->first_name} {$student->last_name}") : 'a student';

                    $actionMap = [
                        'registration' => 'auth.registered',
                        'individual_override' => 'student.year_level_overridden',
                        'bulk_promote' => 'student.bulk_promoted',
                        'academic_year_promotion' => 'student.academic_year_promoted',
                        'student_section_confirmation' => 'student.section_confirmed',
                    ];

                    $description = match ($log->action) {
                        'registration' => "Student {$studentLabel} registered ({$log->new_year_level} Year, Section {$log->new_section}).",
                        'individual_override' => "Updated year level for {$studentLabel} from " . ($log->old_year_level ?? 'Unassigned') . " to {$log->new_year_level}.",
                        'student_section_confirmation' => "{$studentLabel} confirmed section {$log->new_section} ({$log->new_year_level} Year).",
                        'bulk_promote' => "Promoted {$studentLabel} from " . ($log->old_year_level ?? 'Unassigned') . " to {$log->new_year_level}.",
                        'academic_year_promotion' => "Promoted {$studentLabel} from " . ($log->old_year_level ?? 'Unassigned') . " to {$log->new_year_level} for the new academic year.",
                        default => "Updated year level or section for {$studentLabel}.",
                    };

                    $rows[] = [
                        'actor_id' => $actor?->id,
                        'actor_name' => $actor ? trim("{$actor->first_name} {$actor->last_name}") : ($log->actor_id ? 'Administrator' : 'System'),
                        'actor_role' => $actor?->role ?? ($log->actor_id ? 'system_admin' : 'system'),
                        'action' => $actionMap[$log->action] ?? 'student.year_level_updated',
                        'module' => 'Student Management',
                        'description' => $description,
                        'subject_type' => User::class,
                        'subject_id' => $log->user_id,
                        'old_values' => json_encode(array_filter([
                            'year_level' => $log->old_year_level,
                            'section' => $log->old_section,
                        ], fn ($v) => $v !== null)),
                        'new_values' => json_encode(array_filter([
                            'year_level' => $log->new_year_level,
                            'section' => $log->new_section,
                        ], fn ($v) => $v !== null)),
                        'source_type' => YearLevelAuditLog::class,
                        'source_id' => $log->id,
                        'created_at' => $log->created_at ?? now(),
                    ];
                }

                if (! empty($rows)) {
                    DB::table('activity_logs')->insertOrIgnore($rows);
                    $synced += count($rows);
                }
            });

        Cache::put($cacheKey, true, now()->addHours(6));

        return $synced;
    }
}

