# Code Audit Report: cct-wellness-portal

**Date:** October 19, 2026  
**Auditor:** Senior Full-Stack Engineer & Code Auditor  
**Repository:** `cct-wellness-portal`  
**Stack:** Laravel 11.31, PHP 8.2.12, Blade, Tailwind CSS 3.1, Alpine.js 3.4, Vite 6.0, MySQL / MariaDB (SQLite in test), Barryvdh DomPDF 3.1, Spatie Backup 9.3  

---

## Executive Summary Table

| ID | Severity | Category | File:line | One-line description |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | **Critical** | Logic / Runtime | `app/Services/InventoryScoringService.php:18, 121` | Eager load omission causes all custom interpretation ranges to return null and severity labels/colors to be lost |
| **SEC-02** | **Critical** | Data / Logic | `app/Http/Controllers/InventoryController.php:354`, `app/Http/Controllers/Staff/InventorySubmissionController.php:149, 177`, `resources/views/pdf/inventory-submission.blade.php:100` | Submissions render questions and year level from user's current year level instead of submission's historic year level |
| **SEC-03** | **High** | Data / Logic | `app/Http/Controllers/DashboardController.php:77-78` | Eager load limit `take(1)` in query callback truncates flags across recent submissions, hiding flags on 4 of 5 records |
| **SEC-04** | **High** | Data / Logic | `app/Services/InventoryScoringService.php:18-20` | Scoring service queries categories without `year_level` filter, causing cross-year category and item map collisions |
| **SEC-05** | **High** | Config / Security | `composer.lock`, `package-lock.json` | Outdated production and development dependencies with known CVEs (CommonMark, Flysystem, Axios, Shell-quote) |
| **SEC-06** | **Medium** | Data / Logic | `app/Models/QuestionCategory.php:49-74`, `app/Http/Controllers/Staff/QuestionBankController.php:30-32` | N+1 database queries and HTTP GET state mutation (`UPDATE`) executed during category listing |
| **SEC-07** | **Medium** | UI / UX | `resources/views/components/command-palette.blade.php:84`, `resources/views/layouts/staff.blade.php:183-187` | Command palette "Log Out" action is completely dead due to missing modal listener in staff layout |
| **SEC-08** | **Medium** | UI / UX | `resources/views/staff/accounts/index.blade.php:192-198` | Malformed HTML with 7 duplicate unmatched closing tags breaks DOM tree hierarchy |
| **SEC-09** | **Medium** | Config / Deploy | `routes/console.php:36-37` | Automated daily backup uses `--only-db`, contradicting comments and omitting student avatar images and attachments |
| **SEC-10** | **Medium** | Data / Logic | `app/Http/Controllers/Staff/InstitutionController.php:108-118` | Deleting an Academic Year record cascades and deletes Question Categories even when student submissions exist |
| **SEC-11** | **Medium** | Runtime / Routing | `resources/js/app.js:65` | Fallback login redirect points to non-existent route `/portal/dashboard`, causing HTTP 404 on redirect failure |
| **SEC-12** | **Medium** | Config / Security | `app/Http/Middleware/SecurityHeaders.php:33, 37` | Content Security Policy lacks localhost/WebSocket sources, blocking Vite Hot Module Replacement (HMR) in development |
| **SEC-13** | **Low** | Dead Code | `app/Models/InventorySubmission.php:36, 46` | Duplicate Eloquent relationship definitions (`itemTimings()` and `timings()`) for `InventoryItemTiming` |
| **SEC-14** | **Low** | Data / Logic | `app/Models/AcademicYear.php:8-14` | Missing boolean cast on `is_current` attribute causes integer 1/0 return and strict identity comparison failure |
| **SEC-15** | **Low** | UI / UX | `resources/views/layouts/staff.blade.php:56, 59, 60` | Identical SVG icon path reused across three separate staff navigation items |
| **SEC-16** | **Low** | UI / UX | `resources/views/layouts/staff.blade.php:8` | Inconsistent title fallback defaults to `'Laravel'` instead of `'CCT Wellness Portal'` |
| **SEC-17** | **Low** | Dead Code / Hygiene | Root directory (`test_*.php`, `dump_sqlite.php`, `inspect_logo.php`, `temp_*.html`, etc.) | 15 unversioned debug, dump, test, and temporary HTML files littering repository root |
| **SEC-18** | **Low** | UI / UX | `resources/views/layouts/app.blade.php:53` | Redundant `<x-logout-modal />` rendered in app layout alongside navigation's built-in logout modal |

---

## Detailed Findings

---

### SEC-01: Clinical Interpretation Ranges Ignored & Severity Labels Lost
- **Category:** Logic / Runtime
- **File & Line:** [InventoryScoringService.php:18-20, 120-123](file:///c:/xampp/htdocs/cct-wellness-portal/app/Services/InventoryScoringService.php#L18-L20)
- **What is wrong:**
  In `InventoryScoringService::computeScores()`, the `$categories` collection is fetched with eager loading for question items only:
  ```php
  $categories = QuestionCategory::where('academic_year_id', $academicYearId)
      ->with(['questionItems.subcategory'])
      ->get();
  ```
  It does NOT eager load `interpretationRanges`.
  In `getInterpretation($category, $subscale, $score)`:
  ```php
  if (!$category || !$category->relationLoaded('interpretationRanges') || $category->interpretationRanges->isEmpty()) {
      return null;
  }
  ```
  Because `interpretationRanges` is never loaded, `$category->relationLoaded('interpretationRanges')` evaluates to `false` every single time. Consequently, `getInterpretation()` immediately returns `null`.
  In `scoreCAT()` and `scoreStandardCategory()`, `severity_label` and `severity_color` are populated strictly via `$interpretation?->label` and `$interpretation?->color_tag`. Since `$interpretation` is always `null`, these fields are always stored in `inventory_scores` as `null`.
  In `scoreDASS21()`, custom staff-configured interpretation bands are skipped, falling back to hardcoded ranges, and `$severity_color` is always saved as `null`.
- **Why it breaks or risks something:**
  The portal's core clinical feature—custom interpretation ranges configured by counselors in the Question Bank—is completely non-functional. Custom score ranges have zero effect, severity colors are never stored, and standard categories show no interpretation labels.
- **Exact Fix:**
  Eager load `interpretationRanges` in `computeScores()`, or lazy-load if not already loaded:
  ```php
  // app/Services/InventoryScoringService.php line 18
  $categories = QuestionCategory::where('academic_year_id', $academicYearId)
      ->with(['questionItems.subcategory', 'interpretationRanges'])
      ->get();
  ```
  And in `getInterpretation()`:
  ```php
  protected function getInterpretation(?QuestionCategory $category, ?string $subscale, float|int $score): ?InterpretationRange
  {
      if (!$category) {
          return null;
      }

      if (!$category->relationLoaded('interpretationRanges')) {
          $category->load('interpretationRanges');
      }

      if ($category->interpretationRanges->isEmpty()) {
          return null;
      }
      ...
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-02: Submissions Evaluated Against Student's Current Year Level Instead of Historic Year Level
- **Category:** Data / Logic
- **File & Line:** 
  - [InventoryController.php:354-360](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Controllers/InventoryController.php#L354-L360)
  - [InventorySubmissionController.php:149, 177-183](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Controllers/Staff/InventorySubmissionController.php#L149-L183)
  - [inventory-submission.blade.php:100](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/pdf/inventory-submission.blade.php#L100)
- **What is wrong:**
  When loading a historic submission in `showHistory()`, `staff.inventory.show()`, or `exportPdf()`, the application retrieves question categories using the student's *current* live profile year level:
  ```php
  $currentYearLevel = $submission->user?->year_level;
  $categories = QuestionCategory::where('academic_year_id', $academicYear->id)
      ->where('year_level', $currentYearLevel)
      ...
  ```
  Similarly, the PDF header outputs:
  ```blade
  Year Level: <strong>{{ $submission->user?->year_level ?? 'N/A' }} Year</strong>
  ```
  When a student completes an inventory in their 1st Year, and is later promoted to 2nd Year (via bulk promotion or manual update), opening their 1st Year submission matches their stored responses against 2nd Year categories and questions.
- **Why it breaks or risks something:**
  Because question prompts and item numbers differ between year levels, historic answers mismatch or display as `"Question prompt unavailable"`. The PDF report displays an inaccurate year level for the student at the time of assessment. In an institutional wellness/counseling portal, historic assessment integrity is critical.
- **Exact Fix:**
  Add a `year_level` column to `inventory_submissions` (recorded upon creation/submission), and query categories using `$submission->year_level`:
  ```php
  // In InventoryController::submit() / store:
  $submission->update([
      'submitted_at' => now(),
      'year_level' => $user->year_level,
  ]);

  // In showHistory() and InventorySubmissionController::show() / exportPdf():
  $submissionYearLevel = $submission->year_level ?? $submission->user?->year_level;
  $categories = QuestionCategory::where('academic_year_id', $academicYear->id)
      ->where('year_level', $submissionYearLevel)
      ...
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-03: Dashboard Eager Loading Limit Truncates Flags Across All Recent Submissions
- **Category:** Data / Logic
- **File & Line:** [DashboardController.php:77-78](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Controllers/DashboardController.php#L77-L78)
- **What is wrong:**
  In `DashboardController::index()`:
  ```php
  $recentSubmissions = InventorySubmission::with([
      'user',
      'flags' => function ($query) {
          $query->latest()->take(1);
      }
  ])
  ->whereNotNull('submitted_at')
  ->latest('submitted_at')
  ->take(5)
  ->get();
  ```
  In Laravel Eloquent, using `->take(1)` or `LIMIT 1` inside an eager-loading relation closure applies `LIMIT 1` globally to the entire SQL query across all parents. Only the first parent record receives a flag; the other 4 parent records receive empty relations.
- **Why it breaks or risks something:**
  On the staff dashboard, 4 out of the 5 recent submissions display as having zero flags, even if they have unreviewed critical suicide or contradiction flags. Counselors relying on the recent activity widget will miss flagged students.
- **Exact Fix:**
  Remove `->take(1)` from the eager loading query callback and retrieve the latest flag in PHP:
  ```php
  $recentSubmissions = InventorySubmission::with([
      'user',
      'flags' => function ($query) {
          $query->latest();
      }
  ])
  ->whereNotNull('submitted_at')
  ->latest('submitted_at')
  ->take(5)
  ->get();
  ```
  Or define an Eloquent `latestFlag` relationship using `hasOne(InventoryFlag::class)->latestOfMany()`:
  ```php
  // app/Models/InventorySubmission.php
  public function latestFlag(): HasOne
  {
      return $this->hasOne(InventoryFlag::class)->latestOfMany();
  }
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-04: Scoring Service Ignores Year Level Filter Causing Category Collisions
- **Category:** Data / Logic
- **File & Line:** [InventoryScoringService.php:18-20](file:///c:/xampp/htdocs/cct-wellness-portal/app/Services/InventoryScoringService.php#L18-L20)
- **What is wrong:**
  `InventoryScoringService::computeScores()` retrieves all categories for an academic year without filtering by the student's year level:
  ```php
  $categories = QuestionCategory::where('academic_year_id', $academicYearId)
      ->with(['questionItems.subcategory'])
      ->get();
  ```
  Categories across 1st, 2nd, 3rd, and 4th year are loaded simultaneously. The method then maps:
  ```php
  foreach ($categories as $cat) {
      $categoryMap[strtolower($cat->name)] = $cat;
      foreach ($cat->questionItems as $item) {
          $itemMap[$cat->name . '_' . $item->item_number] = $item;
      }
  }
  ```
  Because identical category names (such as `dass21` or `learning_style`) exist across multiple year levels within the same academic year, categories from subsequent year levels overwrite preceding ones in `$categoryMap` and `$itemMap`.
- **Why it breaks or risks something:**
  A 1st Year student's assessment can be evaluated using items, scales, and subcategories defined for 4th Year students. If question counts or scoring weights differ, raw scores and clinical thresholds become invalid.
- **Exact Fix:**
  Determine the submission's target year level and filter `$categories`:
  ```php
  public function computeScores(InventorySubmission $submission): void
  {
      $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
      $academicYearId = $academicYear ? $academicYear->id : null;
      $yearLevel = $submission->user?->year_level;

      $categories = QuestionCategory::where('academic_year_id', $academicYearId)
          ->when($yearLevel, fn($q) => $q->where('year_level', $yearLevel))
          ->with(['questionItems.subcategory', 'interpretationRanges'])
          ->get();
      ...
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-05: Outdated Dependencies with Known Security Advisories
- **Category:** Config / Security
- **File & Line:** [`composer.lock`](file:///c:/xampp/htdocs/cct-wellness-portal/composer.lock), [`package-lock.json`](file:///c:/xampp/htdocs/cct-wellness-portal/package-lock.json)
- **What is wrong:**
  Execution of `composer audit` and `npm audit` returned security advisories:
  1. `league/commonmark` (< 2.6.0): Denial of Service (CVE-2024-43794) & unexpected HTML attributes.
  2. `league/flysystem` (< 3.29.0): CVE-2026-102601.
  3. `symfony/http-foundation` (< 7.1.8): CVE-2026-108082.
  4. `symfony/yaml` (< 7.1.6): CVE-2026-109413.
  5. `shell-quote` (< 1.8.3): Argument injection in npm dependencies (via concurrently).
  6. `axios` (< 1.7.4): SSRF / prototype issues in client bundles.
- **Why it breaks or risks something:**
  Known vulnerabilities in core HTTP parsing, filesystem abstraction, and markdown handling expose the server to denial of service, memory exhaustion, and potential security boundary bypasses.
- **Exact Fix:**
  Update locked dependencies to patched versions:
  ```bash
  composer update league/commonmark league/flysystem symfony/http-foundation symfony/yaml
  npm update axios concurrently
  npm audit fix
  ```
- **Verification Note:** `[CONFIRMED by running]`

---

### SEC-06: N+1 Query & HTTP GET State Mutation in Question Bank Index
- **Category:** Data / Logic
- **File & Line:** 
  - [QuestionCategory.php:49-74](file:///c:/xampp/htdocs/cct-wellness-portal/app/Models/QuestionCategory.php#L49-L74)
  - [QuestionBankController.php:30-32](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Controllers/Staff/QuestionBankController.php#L30-L32)
  - [question-bank/index.blade.php:96](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/staff/question-bank/index.blade.php#L96)
- **What is wrong:**
  In `QuestionCategory::isDynamicallyLocked()`:
  ```php
  $hasActiveSchedule = AssessmentSchedule::where('academic_year_id', $this->academic_year_id)
      ->where('year_level', $this->year_level)
      ...
      ->exists();

  if ($hasActiveSchedule && !$this->is_locked) {
      $this->update(['is_locked' => true]);
  }
  ```
  When `question-bank/index.blade.php` renders categories, it checks `$category->is_locked` inside a loop. This executes an individual query to `assessment_schedules` for every single category (classic N+1 query). Furthermore, if a schedule is active, it executes a database `UPDATE` query directly inside an HTTP `GET` request.
- **Why it breaks or risks something:**
  HTTP GET requests must be safe and idempotent according to RFC 7231. Mutating database state during view rendering causes race conditions, unexpected write locks, and unnecessary database load.
- **Exact Fix:**
  Eager-load or precalculate active schedules in the controller and separate lock evaluation from database writes:
  ```php
  // In QuestionCategory.php
  public function isDynamicallyLocked(): bool
  {
      if ($this->is_locked) {
          return true;
      }

      return AssessmentSchedule::where('academic_year_id', $this->academic_year_id)
          ->where('year_level', $this->year_level)
          ->where('open_date', '<=', now()->toDateString())
          ->where('close_date', '>=', now()->toDateString())
          ->exists();
  }
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-07: Broken "Log Out" Trigger in Staff Command Palette
- **Category:** UI / UX
- **File & Line:** 
  - [command-palette.blade.php:84](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/components/command-palette.blade.php#L84)
  - [staff.blade.php:183-187](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/layouts/staff.blade.php#L183-L187)
- **What is wrong:**
  In `command-palette.blade.php`, item 5 (Log Out) is configured as:
  ```javascript
  url: 'javascript:window.dispatchEvent(new CustomEvent("open-modal", { detail: "confirm-logout" }));'
  ```
  However, in `staff.blade.php`, the logout confirmation modal is implemented inline using Alpine local state `logoutModalOpen`, NOT `<x-modal name="confirm-logout">`. `<x-logout-modal />` is never included in `staff.blade.php`.
- **Why it breaks or risks something:**
  When a staff member opens the Command Palette (Ctrl+K) and clicks "Log Out", nothing happens. The event is dispatched into the void.
- **Exact Fix:**
  Submit the hidden logout form `#logout-form-palette` (already present on line 167 of `command-palette.blade.php`) or dispatch the proper form submit:
  ```javascript
  // In command-palette.blade.php line 84
  { 
      id: 5, 
      name: 'Log Out', 
      description: 'Sign out of your account', 
      url: 'javascript:document.getElementById("logout-form-palette").submit();', 
      icon: '...' 
  }
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-08: Malformed HTML Structure in Staff Accounts Management
- **Category:** UI / UX
- **File & Line:** [accounts/index.blade.php:192-198](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/staff/accounts/index.blade.php#L192-L198)
- **What is wrong:**
  Lines 104-110 close the table and outer wrapper `div` elements:
  ```html
  104:                             </tbody>
  105:                         </table>
  106:                     </div>
  107:                 </div>
  108:             </div>
  109:         </div>
  110:     </div>
  ```
  Immediately following line 191 (`@endforeach` for modals), lines 192-198 re-insert another complete block of closing tags:
  ```html
  192:                             </tbody>
  193:                         </table>
  194:                     </div>
  195:                 </div>
  196:             </div>
  197:         </div>
  198:     </div>
  ```
- **Why it breaks or risks something:**
  Seven orphan closing tags corrupt the DOM structure, prematurely closing parent layout containers and causing CSS layout breaks and accessibility parsing issues.
- **Exact Fix:**
  Remove lines 192-198 from `resources/views/staff/accounts/index.blade.php`.
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-09: Automated Daily Backup Excludes User Files Despite Documented Policy
- **Category:** Config / Deploy
- **File & Line:** [console.php:36-37](file:///c:/xampp/htdocs/cct-wellness-portal/routes/console.php#L36-L37)
- **What is wrong:**
  Comments in `routes/console.php` declare:
  ```php
  // Daily automated full backup (MariaDB database + storage/app/public uploads) at 02:00 AM Manila time
  // Backs up DB dump and all uploaded profile pictures / attachments
  ```
  However, line 37 executes:
  ```php
  Schedule::command('backup:run --only-db')->dailyAt('02:00')->...
  ```
- **Why it breaks or risks something:**
  The `--only-db` flag strictly skips `storage/app/public`. If the server disk suffers failure or data loss, all student profile pictures and announcement attachments are permanently lost because the automated schedule never backs them up.
- **Exact Fix:**
  Remove `--only-db` to create a complete database and file backup:
  ```php
  // routes/console.php line 37
  Schedule::command('backup:run')
      ->dailyAt('02:00')
      ->timezone('Asia/Manila')
      ->appendOutputTo(storage_path('logs/backup.log'));
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-10: Deleting Academic Year Silently Cascades and Destroys Submissions Data
- **Category:** Data / Logic
- **File & Line:** [InstitutionController.php:108-118](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Controllers/Staff/InstitutionController.php#L108-L118)
- **What is wrong:**
  In `InstitutionController::destroyAcademicYear()`:
  ```php
  if ($academic_year->assessmentSchedules()->count() > 0) {
      return back()->withErrors(['error' => 'Cannot delete Academic Year with existing schedules.']);
  }
  ```
  The controller checks whether `assessmentSchedules` exist, but does not check if `InventorySubmission::where('academic_year', $academic_year->label)->exists()`.
  If an admin deletes the schedules and then deletes the academic year, the database migration's `cascadeOnDelete` deletes all `QuestionCategory` records associated with that academic year.
- **Why it breaks or risks something:**
  The `inventory_submissions` table records submissions tied to the academic year label string. When the categories are deleted, all historic submissions for that year lose their question associations, breaking student history and staff verification screens.
- **Exact Fix:**
  Add a guard in `InstitutionController::destroyAcademicYear()`:
  ```php
  if (InventorySubmission::where('academic_year', $academic_year->label)->exists()) {
      return back()->withErrors(['error' => 'Cannot delete an Academic Year that has recorded student submissions.']);
  }
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-11: Fallback Login Route `/portal/dashboard` Does Not Exist
- **Category:** Runtime / Routing
- **File & Line:** [app.js:65](file:///c:/xampp/htdocs/cct-wellness-portal/resources/js/app.js#L65)
- **What is wrong:**
  In `resources/js/app.js` line 65:
  ```javascript
  window.location.href = data.redirect || '/portal/dashboard';
  ```
  The route prefix `/portal/dashboard` does not exist in `routes/web.php`. The application uses `/dashboard`.
- **Why it breaks or risks something:**
  If the server's JSON response ever omits `data.redirect` or is empty, the browser navigates to `/portal/dashboard`, returning an HTTP 404 Not Found error.
- **Exact Fix:**
  Update the fallback route to `/dashboard`:
  ```javascript
  window.location.href = data.redirect || '/dashboard';
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-12: Content Security Policy Prevents Vite Hot Module Replacement (HMR)
- **Category:** Config / Security
- **File & Line:** [SecurityHeaders.php:33, 37](file:///c:/xampp/htdocs/cct-wellness-portal/app/Http/Middleware/SecurityHeaders.php#L33-L37)
- **What is wrong:**
  In `SecurityHeaders::handle()`:
  ```php
  $cspDirectives = [
      "default-src 'self'",
      "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' cdn.jsdelivr.net unpkg.com",
      ...
      "connect-src 'self'",
  ];
  ```
  In local development (`npm run dev`), Vite runs on `http://localhost:5173` and uses a WebSocket connection at `ws://localhost:5173` for HMR. Neither URL is allowed by `connect-src 'self'` or `script-src`.
- **Why it breaks or risks something:**
  Browsers block Vite scripts and WebSocket connections with CSP violations, breaking local asset hot-reloading and requiring developers to disable middleware during development.
- **Exact Fix:**
  Add local development exceptions when running in non-production environments:
  ```php
  $connectSrc = "connect-src 'self'";
  $scriptSrc = "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' cdn.jsdelivr.net unpkg.com";

  if (!app()->environment('production')) {
      $connectSrc .= " http://localhost:5173 ws://localhost:5173";
      $scriptSrc .= " http://localhost:5173";
  }
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-13: Duplicate Relationship Defined on InventorySubmission
- **Category:** Dead Code
- **File & Line:** [InventorySubmission.php:36-39, 46-49](file:///c:/xampp/htdocs/cct-wellness-portal/app/Models/InventorySubmission.php#L36-L49)
- **What is wrong:**
  `InventorySubmission` defines both `itemTimings()` and `timings()` with identical implementations:
  ```php
  public function itemTimings(): HasMany
  {
      return $this->hasMany(InventoryItemTiming::class);
  }

  public function timings(): HasMany
  {
      return $this->hasMany(InventoryItemTiming::class);
  }
  ```
- **Why it breaks or risks something:**
  Redundant method definitions create code ambiguity and risk diverging implementations.
- **Exact Fix:**
  Consolidate into `itemTimings()` and alias or deprecate `timings()`.
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-14: Missing Boolean Cast on AcademicYear Model
- **Category:** Data / Logic
- **File & Line:** [AcademicYear.php:8-14](file:///c:/xampp/htdocs/cct-wellness-portal/app/Models/AcademicYear.php#L8-L14)
- **What is wrong:**
  `AcademicYear` does not include `$casts` for `is_current`.
- **Why it breaks or risks something:**
  When fetched via Eloquent, `$academicYear->is_current` is returned as integer `1`/`0` (or `"1"`/`"0"`). Any strict boolean comparison (`$academicYear->is_current === true`) fails.
- **Exact Fix:**
  Add the cast attribute:
  ```php
  protected $casts = [
      'is_current' => 'boolean',
  ];
  ```
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-15: Identical Icon Reused Across Three Distinct Staff Navigation Items
- **Category:** UI / UX
- **File & Line:** [staff.blade.php:56, 59, 60](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/layouts/staff.blade.php#L56-L60)
- **What is wrong:**
  In `resources/views/layouts/staff.blade.php`, the navigation items for "Account Management", "Student Profiles", and "Year Levels" all share the identical SVG path string (`M15 19.128a9.38...`).
- **Why it breaks or risks something:**
  Visual duplication impairs staff usability and quick-scan recognition in the sidebar.
- **Exact Fix:**
  Provide distinct Heroicons for Student Profiles (e.g., `user-group`) and Year Levels (e.g., `academic-cap`).
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-16: Application Title Fallback Inconsistency in Staff Layout
- **Category:** UI / UX
- **File & Line:** [staff.blade.php:8](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/layouts/staff.blade.php#L8)
- **What is wrong:**
  Line 8 reads:
  ```html
  <title>{{ config('app.name', 'Laravel') }} - Staff</title>
  ```
  Whereas `app.blade.php` and `auth.blade.php` default to `'CCT Wellness Portal'`.
- **Why it breaks or risks something:**
  If `APP_NAME` is not explicitly set in the environment, the browser tab reads "Laravel - Staff" instead of "CCT Wellness Portal - Staff".
- **Exact Fix:**
  Change fallback string to `'CCT Wellness Portal'`.
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-17: Development and Scratch Scripts Present in Root Directory
- **Category:** Dead Code / Hygiene
- **File & Line:** Root directory (`dump_sqlite.php`, `inspect_logo.php`, `test_auth.php`, `test_flags.php`, `test_login_api.py`, `test_render.php`, `test_script.php`, `test_store.php`, `test_tx.php`, `test_update.php`, `cookies.txt`, `temp_render.html`, `temp_student.html`, `temp_student2.html`, `test_output.html`, `login_output.txt`)
- **What is wrong:**
  15 ad-hoc test, dump, and output files remain in the root directory.
- **Why it breaks or risks something:**
  These files expose internal testing details and could be accidentally exposed on a public server.
- **Exact Fix:**
  Delete temporary scratch files and add patterns to `.gitignore`.
- **Verification Note:** `[CONFIRMED by reading]`

---

### SEC-18: Redundant Logout Modal Included in App Layout
- **Category:** UI / UX
- **File & Line:** [app.blade.php:53](file:///c:/xampp/htdocs/cct-wellness-portal/resources/views/layouts/app.blade.php#L53)
- **What is wrong:**
  `app.blade.php` includes `<x-logout-modal />`, while `navigation.blade.php` already contains its own modal.
- **Why it breaks or risks something:**
  Unnecessary DOM bloat and confusion over which modal instance is active.
- **Exact Fix:**
  Remove `<x-logout-modal />` from `app.blade.php`.
- **Verification Note:** `[CONFIRMED by reading]`

---

## Not Checked
The following areas could not be inspected or executed within the local audit environment:
1. **Production Mail & SMTP Delivery:** Outbound mail delivery to real institutional addresses (`@citycollegeoftagaytay.edu.ph`) could not be verified live without active institutional mail servers (tested with `mail.mailer=log` and test suite).
2. **Production Cloudflare Tunnel:** Execution of `cloudflared` in `start-tunnel.bat` could not be tested against external network ingress in this isolated container.
3. **Cross-Browser Mobile Rendering:** Testing was performed against local Chromium engine and DOM source inspection; physical iOS Safari / WebKit quirks (beyond CSS `min-height` and font size audits) were not verified on physical hardware.
4. **Third-Party Off-Server Backup Storage:** S3 / R2 / GCS cloud destination uploads could not be tested as only local disk backup storage is currently configured.
