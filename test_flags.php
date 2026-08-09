<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventorySubmission;
use App\Services\InventoryFlaggingService;
use App\Models\InventoryFlag;

$submission = InventorySubmission::latest('id')->first();
if (!$submission) {
    echo "No submission found\n";
    exit;
}
echo "Submission ID: " . $submission->id . "\n";
echo "Total responses: " . $submission->responses()->count() . "\n";

$service = new InventoryFlaggingService();
$service->analyze($submission);

$flags = InventoryFlag::where('inventory_submission_id', $submission->id)->get();
echo "Flags generated: " . $flags->count() . "\n";
foreach($flags as $f) {
    echo "- " . $f->flag_type . " / " . $f->category . "\n";
}
