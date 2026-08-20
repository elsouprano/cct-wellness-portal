<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/staff/question-bank', 'POST', [
    'name' => 'Test Default Options',
    'year_level' => '1st',
    'display_order' => 99,
    'scale_type' => 'multiple_choice_unscored',
    'default_options' => "Opt 1\nOpt 2",
    'items' => [
        [
            'item_number' => 1,
            'prompt' => 'Test Q1',
            'options' => ''
        ]
    ]
]);

$controller = app(\App\Http\Controllers\Staff\QuestionBankController::class);
try {
    $controller->store($request);
    $c = \App\Models\QuestionCategory::where('name', 'Test Default Options')->first();
    echo "Saved default_options: " . json_encode($c->default_options) . "\n";
    echo "Saved item options: " . json_encode($c->questionItems->first()->options) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
