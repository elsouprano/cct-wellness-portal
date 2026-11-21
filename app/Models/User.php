<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_initial',
        'last_name',
        'birthdate',
        'program',
        'section',
        'contact_number',
        'address_line1',
        'city',
        'province',
        'email',
        'password',
        'year_level',
        'profile_picture_path',
        'program_id',
        'department_id',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'needs_section_confirmation',
    ];

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function structuredProgram()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function assignedDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function assignedDepartments()
    {
        return $this->belongsToMany(Department::class, 'counselor_departments');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Request-scoped cache for staff department/program access scopes.
     *
     * @var \WeakMap<object, array<string, mixed>>|null
     */
    protected static ?\WeakMap $requestScopeCache = null;

    public static function clearRequestScopeCache(): void
    {
        static::$requestScopeCache = null;
    }

    protected static function scopeCacheBucket(): ?\ArrayObject
    {
        if (! app()->bound('request')) {
            return null;
        }

        $req = app('request');
        if (! $req || ! method_exists($req, 'route') || $req->route() === null) {
            return null;
        }

        if (static::$requestScopeCache === null) {
            static::$requestScopeCache = new \WeakMap();
        }

        if (! isset(static::$requestScopeCache[$req])) {
            static::$requestScopeCache[$req] = new \ArrayObject();
        }

        return static::$requestScopeCache[$req];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'program_id' => 'integer',
            'department_id' => 'integer',
            'is_paying_student' => 'boolean',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'needs_section_confirmation' => 'boolean',
        ];
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function inventorySubmissions()
    {
        return $this->hasMany(InventorySubmission::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function isCounselor(): bool
    {
        return $this->role === 'guidance_counselor';
    }

    /**
     * Get the department IDs assigned to this staff user.
     *
     * @return array<int>
     */
    public function getAllowedDepartmentIds(): array
    {
        $bucket = static::scopeCacheBucket();
        $cacheKey = "depts_{$this->id}_{$this->role}_{$this->department_id}";
        if ($bucket !== null && isset($bucket[$cacheKey])) {
            return $bucket[$cacheKey];
        }

        if ($this->isAdmin()) {
            $result = Department::notArchived()->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($bucket !== null) {
                $bucket[$cacheKey] = $result;
            }

            return $result;
        }

        if (! $this->isCounselor()) {
            return [];
        }

        // Pivot assignments are authoritative once present so unchecked departments
        // cannot be preserved by a stale legacy users.department_id column.
        if ($this->relationLoaded('assignedDepartments')) {
            $pivotDeptIds = $this->assignedDepartments
                ->filter(fn ($dept) => $dept->archived_at === null)
                ->pluck('id')
                ->all();
        } else {
            $pivotDeptIds = $this->assignedDepartments()
                ->whereNull('departments.archived_at')
                ->pluck('departments.id')
                ->all();
        }

        if (! empty($pivotDeptIds)) {
            $result = array_values(array_unique(array_map('intval', $pivotDeptIds)));
        } elseif ($this->department_id) {
            $activeLegacy = Department::notArchived()->whereKey($this->department_id)->exists();
            $result = $activeLegacy ? [(int) $this->department_id] : [];
        } else {
            $result = [];
        }

        if ($bucket !== null) {
            $bucket[$cacheKey] = $result;
        }

        return $result;
    }

    /**
     * Fetch allowed Program rows (id, code, name) in a single request-cached query.
     *
     * @return \Illuminate\Support\Collection<int, Program>|null
     */
    public function getAllowedProgramRecords(): ?\Illuminate\Support\Collection
    {
        if ($this->isAdmin()) {
            return null;
        }

        if (! $this->isCounselor()) {
            return collect();
        }

        $deptIds = $this->getAllowedDepartmentIds();
        if (empty($deptIds)) {
            return collect();
        }

        $bucket = static::scopeCacheBucket();
        $cacheKey = 'progs_' . implode('_', $deptIds);
        if ($bucket !== null && isset($bucket[$cacheKey])) {
            return $bucket[$cacheKey];
        }

        $programs = Program::whereIn('department_id', $deptIds)->get(['id', 'code', 'name']);

        if ($bucket !== null) {
            $bucket[$cacheKey] = $programs;
        }

        return $programs;
    }

    /**
     * Get the program IDs accessible by this staff user.
     * Returns null for system admins (unrestricted),
     * an array of program IDs for assigned counselors,
     * or an empty array for unassigned counselors or students.
     *
     * @return array<int>|null
     */
    public function getAllowedProgramIds(): ?array
    {
        $programs = $this->getAllowedProgramRecords();
        if ($programs === null) {
            return null;
        }

        return $programs->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Get allowed legacy program codes and names for counselors in 0 extra queries.
     *
     * @return array<string>
     */
    public function getAllowedProgramStrings(): array
    {
        $programs = $this->getAllowedProgramRecords();
        if ($programs === null || $programs->isEmpty()) {
            return [];
        }

        return array_values(array_unique(array_filter(array_merge(
            $programs->pluck('code')->all(),
            $programs->pluck('name')->all(),
        ))));
    }

    public function canAccessStudent(User $student): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isCounselor()) {
            return false;
        }

        $allowedPrograms = $this->getAllowedProgramIds();
        if (empty($allowedPrograms)) {
            return false;
        }

        if ($student->role === 'student') {
            if ($student->program_id !== null) {
                return in_array((int) $student->program_id, $allowedPrograms, true);
            }

            if ($student->program) {
                return in_array($student->program, $this->getAllowedProgramStrings(), true);
            }
        }

        return false;
    }

    public function canAccessSubmission(InventorySubmission $submission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isCounselor()) {
            return false;
        }

        $student = $submission->user;
        if (! $student) {
            return false;
        }

        return $this->canAccessStudent($student);
    }

    public function canManageSchedule(AssessmentSchedule $schedule): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isCounselor()) {
            return false;
        }

        $allowedPrograms = $this->getAllowedProgramIds();
        if (empty($allowedPrograms)) {
            return false;
        }

        return $schedule->program_id !== null
            && in_array((int) $schedule->program_id, $allowedPrograms, true);
    }

    public function scopeAccessibleByStaff(Builder $query, User $staff): Builder
    {
        if ($staff->isAdmin()) {
            return $query;
        }

        $allowedProgramIds = $staff->getAllowedProgramIds();
        if (empty($allowedProgramIds)) {
            return $query->whereRaw('1 = 0');
        }

        $allowedStrings = $staff->getAllowedProgramStrings();

        return $query->where(function ($q) use ($allowedProgramIds, $allowedStrings) {
            $q->whereIn('program_id', $allowedProgramIds);
            if (! empty($allowedStrings)) {
                $q->orWhere(function ($fallbackQ) use ($allowedStrings) {
                    $fallbackQ->whereNull('program_id')
                              ->whereIn('program', $allowedStrings);
                });
            }
        });
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_picture_path) {
            return Storage::url($this->profile_picture_path);
        }

        $initials = strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="#4f7c68" />
            <text x="50" y="50" font-family="sans-serif" font-size="40" font-weight="bold" fill="#ffffff" dominant-baseline="central" text-anchor="middle">'.htmlspecialchars($initials).'</text>
        </svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function discardUnfinishedDraft(): int
    {
        $drafts = $this->inventorySubmissions()->whereNull('submitted_at')->get();
        $count = $drafts->count();

        foreach ($drafts as $draft) {
            $draft->responses()->delete();
            $draft->itemTimings()->delete();
            $draft->delete();
        }

        return $count;
    }

    public function sectionMatchesYearLevel(): bool
    {
        if (empty($this->section) || empty($this->year_level)) {
            return true;
        }

        $map = [
            '1st' => '1',
            '2nd' => '2',
            '3rd' => '3',
            '4th' => '4',
        ];

        $expectedDigit = $map[$this->year_level] ?? null;
        if (! $expectedDigit) {
            return true;
        }

        if (preg_match('/^(\d)/', trim($this->section), $matches)) {
            return $matches[1] === $expectedDigit;
        }

        return true;
    }

    public function syncSectionToYearLevel(string $newLevel): bool
    {
        $map = [
            '1st' => '1',
            '2nd' => '2',
            '3rd' => '3',
            '4th' => '4',
        ];

        if (! isset($map[$newLevel])) {
            return true;
        }

        $digit = $map[$newLevel];

        if (empty($this->section) || $this->section === 'N/A') {
            return false;
        }

        if (preg_match('/^\d(?=[-\s])/', $this->section)) {
            $this->section = preg_replace('/^\d/', $digit, $this->section);

            return true;
        }

        return false;
    }

    /**
     * Get valid sections for the student based on program and year level.
     *
     * @return array<string>
     */
    public function getValidSections(?string $targetYearLevel = null): array
    {
        $level = $targetYearLevel ?: $this->year_level;
        $map = [
            '1st' => 1,
            '2nd' => 2,
            '3rd' => 3,
            '4th' => 4,
        ];

        $digit = $map[$level] ?? 1;

        $sections = [
            "{$digit}-1",
            "{$digit}-2",
            "{$digit}-3",
            "{$digit}-4",
        ];

        $existing = self::where('role', 'student')
            ->when(
                $this->program_id,
                fn ($q) => $q->where('program_id', $this->program_id),
                fn ($q) => $q->when($this->program, fn ($sub) => $sub->where('program', $this->program))
            )
            ->where('year_level', $level)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->where('section', '!=', 'N/A')
            ->distinct()
            ->pluck('section')
            ->filter(fn ($sec) => preg_match("/^{$digit}-/i", (string) $sec))
            ->all();

        $all = array_values(array_unique(array_merge($sections, $existing)));
        natcasesort($all);

        return array_values($all);
    }
}
