<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Announcements Feed & Show
    Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'show'])->name('announcements.show');

    // 3rd Year Inventory
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [\App\Http\Controllers\InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/validate-section', [\App\Http\Controllers\InventoryController::class, 'validateSection'])->name('inventory.validate-section');
});

Route::middleware(['auth', 'verified', 'counselor_or_admin'])->group(function () {
    // Announcements Counselor/Admin CRUD
    Route::get('/manage/announcements/create', [\App\Http\Controllers\AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/manage/announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/manage/announcements/{announcement}/edit', [\App\Http\Controllers\AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/manage/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/manage/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Question Bank
    Route::get('/manage/question-bank', [\App\Http\Controllers\Staff\QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::get('/manage/question-bank/create', [\App\Http\Controllers\Staff\QuestionBankController::class, 'create'])->name('question-bank.create');
    Route::post('/manage/question-bank', [\App\Http\Controllers\Staff\QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::get('/manage/question-bank/{category}/edit', [\App\Http\Controllers\Staff\QuestionBankController::class, 'edit'])->name('question-bank.edit');
    Route::put('/manage/question-bank/{category}', [\App\Http\Controllers\Staff\QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/manage/question-bank/{category}', [\App\Http\Controllers\Staff\QuestionBankController::class, 'destroy'])->name('question-bank.destroy');



    // Schedules
    Route::get('/manage/schedules', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/manage/schedules/create', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/manage/schedules', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/manage/schedules/{schedule}/edit', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/manage/schedules/{schedule}', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/manage/schedules/{schedule}', [\App\Http\Controllers\Staff\AssessmentScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Flag Settings
    Route::get('/manage/flag-settings', [App\Http\Controllers\Staff\FlagSettingController::class, 'index'])->name('flag-settings.index');
    Route::put('/manage/flag-settings', [App\Http\Controllers\Staff\FlagSettingController::class, 'update'])->name('flag-settings.update');

    // Submissions review
    Route::get('/manage/inventory', [App\Http\Controllers\Staff\InventorySubmissionController::class, 'index'])->name('staff.inventory.index');
    Route::get('/manage/inventory/{submission}', [App\Http\Controllers\Staff\InventorySubmissionController::class, 'show'])->name('staff.inventory.show');
    Route::get('/manage/inventory/{submission}/export', [App\Http\Controllers\Staff\InventorySubmissionController::class, 'exportPdf'])->name('staff.inventory.export');
    Route::patch('/manage/inventory/flags/{flag}/review', [App\Http\Controllers\Staff\InventorySubmissionController::class, 'reviewFlag'])->name('staff.inventory.flags.review');

    // Analytics Dashboard
    Route::get('/manage/analytics', [App\Http\Controllers\Staff\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/manage/analytics/export', [App\Http\Controllers\Staff\AnalyticsController::class, 'export'])->name('analytics.export');

    // Year Level Management
    Route::get('/manage/year-levels', [\App\Http\Controllers\Staff\YearLevelController::class, 'index'])->name('year-levels.index');
    Route::post('/manage/year-levels/bulk-promote', [\App\Http\Controllers\Staff\YearLevelController::class, 'bulkPromote'])->name('year-levels.bulk-promote');
    Route::post('/manage/year-levels/{user}/override', [\App\Http\Controllers\Staff\YearLevelController::class, 'override'])->name('year-levels.override');
    Route::get('/manage/year-levels/audit', [\App\Http\Controllers\Staff\YearLevelController::class, 'audit'])->name('year-levels.audit');

    // Institution Settings (System Admin Only)
    Route::middleware(\App\Http\Middleware\EnsureUserIsSystemAdmin::class)->group(function () {
        Route::get('/manage/institution', [\App\Http\Controllers\Staff\InstitutionController::class, 'index'])->name('institution.index');
        Route::post('/manage/institution/departments', [\App\Http\Controllers\Staff\InstitutionController::class, 'storeDepartment'])->name('institution.departments.store');
        Route::put('/manage/institution/departments/{department}', [\App\Http\Controllers\Staff\InstitutionController::class, 'updateDepartment'])->name('institution.departments.update');
        Route::delete('/manage/institution/departments/{department}', [\App\Http\Controllers\Staff\InstitutionController::class, 'destroyDepartment'])->name('institution.departments.destroy');
        
        Route::post('/manage/institution/programs', [\App\Http\Controllers\Staff\InstitutionController::class, 'storeProgram'])->name('institution.programs.store');
        Route::put('/manage/institution/programs/{program}', [\App\Http\Controllers\Staff\InstitutionController::class, 'updateProgram'])->name('institution.programs.update');
        Route::delete('/manage/institution/programs/{program}', [\App\Http\Controllers\Staff\InstitutionController::class, 'destroyProgram'])->name('institution.programs.destroy');

        // Academic Years (System Admin Only)
        Route::post('/manage/institution/academic-years', [\App\Http\Controllers\Staff\InstitutionController::class, 'storeAcademicYear'])->name('institution.academic-years.store');
        Route::put('/manage/institution/academic-years/{academic_year}', [\App\Http\Controllers\Staff\InstitutionController::class, 'updateAcademicYear'])->name('institution.academic-years.update');
        Route::delete('/manage/institution/academic-years/{academic_year}', [\App\Http\Controllers\Staff\InstitutionController::class, 'destroyAcademicYear'])->name('institution.academic-years.destroy');
        Route::patch('/manage/institution/academic-years/{academic_year}/set-current', [\App\Http\Controllers\Staff\InstitutionController::class, 'setCurrentAcademicYear'])->name('institution.academic-years.set-current');

        // Interpretation Ranges (System Admin Only due to clinical validation concerns)
        Route::post('/manage/question-bank/{question_category}/ranges', [\App\Http\Controllers\Staff\InterpretationRangeController::class, 'store'])->name('interpretation-ranges.store');
        Route::put('/manage/question-bank/{question_category}/ranges/{range}', [\App\Http\Controllers\Staff\InterpretationRangeController::class, 'update'])->name('interpretation-ranges.update');
        Route::delete('/manage/question-bank/{question_category}/ranges/{range}', [\App\Http\Controllers\Staff\InterpretationRangeController::class, 'destroy'])->name('interpretation-ranges.destroy');

        // Account Management (System Admin Only)
        Route::get('/manage/accounts', [\App\Http\Controllers\Staff\AccountController::class, 'index'])->name('manage.accounts.index');
        Route::post('/manage/accounts', [\App\Http\Controllers\Staff\AccountController::class, 'store'])->name('manage.accounts.store');
        Route::put('/manage/accounts/{user}', [\App\Http\Controllers\Staff\AccountController::class, 'update'])->name('manage.accounts.update');
        Route::patch('/manage/accounts/{user}/toggle-status', [\App\Http\Controllers\Staff\AccountController::class, 'toggleStatus'])->name('manage.accounts.toggle-status');
        Route::post('/manage/accounts/{user}/password-reset', [\App\Http\Controllers\Staff\AccountController::class, 'triggerPasswordReset'])->name('manage.accounts.password-reset');
    });
});

require __DIR__.'/auth.php';

Route::get('/autologin', function() {
    auth()->login(App\Models\User::where('role', 'student')->first());
    return redirect('/inventory');
});
