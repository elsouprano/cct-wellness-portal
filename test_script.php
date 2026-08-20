<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$c = \App\Models\QuestionCategory::where('scale_type', 'multiple_choice_unscored')->first();
if(!$c) die('no category');
echo json_encode(['default_options' => $c->default_options, 'items' => $c->questionItems->map(fn($i) => $i->options)->toArray()], JSON_PRETTY_PRINT);
