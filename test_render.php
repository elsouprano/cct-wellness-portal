<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\QuestionCategory::where('name', 'Test Default Options')->first();
$item = $c->questionItems->first();

// This is the logic in the blade template:
$itemOptions = $item->options ?: ($c->default_options ?? []);
echo "itemOptions result: " . json_encode($itemOptions) . "\n";
