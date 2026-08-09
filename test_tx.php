<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::factory()->create(['role' => 'student', 'year_level' => '3rd']);
$year = App\Models\AcademicYear::where('is_current', true)->first();
$submission = App\Models\InventorySubmission::create([
    'user_id' => $user->id,
    'academic_year' => $year->label,
    'started_at' => now(),
]);

Illuminate\Support\Facades\DB::transaction(function() use ($submission) {
    App\Models\InventoryResponse::create([
        'inventory_submission_id' => $submission->id,
        'category' => 'DASS21',
        'item_number' => 1,
        'response_value' => '0',
    ]);
    $scoring = new App\Services\InventoryScoringService();
    $scoring->computeScores($submission);
    
    $flagging = new App\Services\InventoryFlaggingService();
    $flagging->analyze($submission);
});
echo "Success! Flags count: " . App\Models\InventoryFlag::where('inventory_submission_id', $submission->id)->count() . "\n";
