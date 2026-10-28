<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\AccountController;
use App\Http\Controllers\Staff\AnalyticsController;
use App\Http\Controllers\Staff\AssessmentScheduleController;
use App\Http\Controllers\Staff\FlagSettingController;
use App\Http\Controllers\Staff\InstitutionController;
use App\Http\Controllers\Staff\InterpretationRangeController;
use App\Http\Controllers\Staff\InventorySubmissionController;
use App\Http\Controllers\Staff\QuestionBankController;
use App\Http\Controllers\Staff\StudentController;
use App\Http\Controllers\Staff\YearLevelController;
use App\Http\Middleware\EnsureUserIsSystemAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/portal/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Announcements Feed & Show
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

    // 3rd Year Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/validate-section', [InventoryController::class, 'validateSection'])->name('inventory.validate-section');

    // Inventory History
    Route::get('/inventory/history', [InventoryController::class, 'history'])->name('inventory.history');
    Route::get('/inventory/history/{id}', [InventoryController::class, 'showHistory'])->name('inventory.history.show');

});

Route::middleware(['auth', 'verified', 'counselor_or_admin'])->group(function () {
    // Announcements Counselor/Admin CRUD
    Route::get('/manage/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/manage/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/manage/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/manage/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('/manage/announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
    Route::delete('/manage/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Question Bank
    Route::get('/manage/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::get('/manage/question-bank/create', [QuestionBankController::class, 'create'])->name('question-bank.create');
    Route::post('/manage/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::get('/manage/question-bank/{category}/edit', [QuestionBankController::class, 'edit'])->name('question-bank.edit');
    Route::put('/manage/question-bank/{category}', [QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/manage/question-bank/{category}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');

    // Schedules
    Route::get('/manage/schedules', [AssessmentScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/manage/schedules/create', [AssessmentScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/manage/schedules', [AssessmentScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/manage/schedules/{schedule}/edit', [AssessmentScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/manage/schedules/{schedule}', [AssessmentScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/manage/schedules/{schedule}', [AssessmentScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Flag Settings
    Route::get('/manage/flag-settings', [FlagSettingController::class, 'index'])->name('flag-settings.index');
    Route::put('/manage/flag-settings', [FlagSettingController::class, 'update'])->name('flag-settings.update');

    // Submissions review
    Route::get('/manage/inventory', [InventorySubmissionController::class, 'index'])->name('staff.inventory.index');
    Route::get('/manage/inventory/{submission}', [InventorySubmissionController::class, 'show'])->name('staff.inventory.show');
    Route::get('/manage/inventory/{submission}/export', [InventorySubmissionController::class, 'exportPdf'])->name('staff.inventory.export');
    Route::patch('/manage/inventory/flags/{flag}/review', [InventorySubmissionController::class, 'reviewFlag'])->name('staff.inventory.flags.review');

    // Student Directory
    Route::get('/manage/students', [StudentController::class, 'index'])->name('staff.students.index');
    Route::get('/manage/students/{student}', [StudentController::class, 'show'])->name('staff.students.show');

    // Analytics Dashboard
    Route::get('/manage/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/manage/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Year Level Management
    Route::get('/manage/year-levels', [YearLevelController::class, 'index'])->name('year-levels.index');
    Route::post('/manage/year-levels/bulk-promote', [YearLevelController::class, 'bulkPromote'])->name('year-levels.bulk-promote');
    Route::post('/manage/year-levels/{user}/override', [YearLevelController::class, 'override'])
        ->middleware(EnsureUserIsSystemAdmin::class)
        ->name('year-levels.override');
    Route::get('/manage/year-levels/audit', [YearLevelController::class, 'audit'])->name('year-levels.audit');

    // Institution Settings (System Admin Only)
    Route::middleware(EnsureUserIsSystemAdmin::class)->group(function () {
        Route::get('/manage/institution', [InstitutionController::class, 'index'])->name('institution.index');
        Route::post('/manage/institution/departments', [InstitutionController::class, 'storeDepartment'])->name('institution.departments.store');
        Route::put('/manage/institution/departments/{department}', [InstitutionController::class, 'updateDepartment'])->name('institution.departments.update');
        Route::delete('/manage/institution/departments/{department}', [InstitutionController::class, 'destroyDepartment'])->name('institution.departments.destroy');

        Route::post('/manage/institution/programs', [InstitutionController::class, 'storeProgram'])->name('institution.programs.store');
        Route::put('/manage/institution/programs/{program}', [InstitutionController::class, 'updateProgram'])->name('institution.programs.update');
        Route::delete('/manage/institution/programs/{program}', [InstitutionController::class, 'destroyProgram'])->name('institution.programs.destroy');

        // Academic Years (System Admin Only)
        Route::post('/manage/institution/academic-years', [InstitutionController::class, 'storeAcademicYear'])->name('institution.academic-years.store');
        Route::put('/manage/institution/academic-years/{academic_year}', [InstitutionController::class, 'updateAcademicYear'])->name('institution.academic-years.update');
        Route::delete('/manage/institution/academic-years/{academic_year}', [InstitutionController::class, 'destroyAcademicYear'])->name('institution.academic-years.destroy');
        Route::patch('/manage/institution/academic-years/{academic_year}/set-current', [InstitutionController::class, 'setCurrentAcademicYear'])->name('institution.academic-years.set-current');

        // Interpretation Ranges (System Admin Only due to clinical validation concerns)
        Route::post('/manage/question-bank/{question_category}/ranges', [InterpretationRangeController::class, 'store'])->name('interpretation-ranges.store');
        Route::put('/manage/question-bank/{question_category}/ranges/{range}', [InterpretationRangeController::class, 'update'])->name('interpretation-ranges.update');
        Route::delete('/manage/question-bank/{question_category}/ranges/{range}', [InterpretationRangeController::class, 'destroy'])->name('interpretation-ranges.destroy');

        // Account Management (System Admin Only)
        Route::get('/manage/accounts', [AccountController::class, 'index'])->name('manage.accounts.index');
        Route::post('/manage/accounts', [AccountController::class, 'store'])->name('manage.accounts.store');
        Route::put('/manage/accounts/{user}', [AccountController::class, 'update'])->name('manage.accounts.update');
        Route::patch('/manage/accounts/{user}/toggle-status', [AccountController::class, 'toggleStatus'])->name('manage.accounts.toggle-status');
        Route::post('/manage/accounts/{user}/password-reset', [AccountController::class, 'triggerPasswordReset'])->name('manage.accounts.password-reset');

        // Student Status and Archiving (System Admin Only)
        Route::patch('/manage/students/{student}/archive', [StudentController::class, 'archive'])->name('staff.students.archive');
        Route::patch('/manage/students/{student}/unarchive', [StudentController::class, 'unarchive'])->name('staff.students.unarchive');
    });
});

require __DIR__.'/auth.php';
