<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\QuestionCategory::where('name', 'Test Default Options')->first();
$request = \Illuminate\Http\Request::create('/staff/question-bank/' . $c->id, 'PUT', [
    'name' => 'Test Default Options Updated',
    'year_level' => '1st',
    'display_order' => 99,
    // Note: scale_type is OMITTED because it would be disabled on the frontend
    'default_options' => "New Opt 1\nNew Opt 2",
    'items' => [
        [
            'id' => $c->questionItems->first()->id,
            'item_number' => 1,
            'prompt' => 'Test Q1 Updated',
            'options' => ''
        ]
    ]
]);

$controller = app(\App\Http\Controllers\Staff\QuestionBankController::class);
try {
    $controller->update($request, $c);
    $c = $c->fresh();
    echo "Saved default_options: " . json_encode($c->default_options) . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation Error: " . json_encode($e->errors(), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
