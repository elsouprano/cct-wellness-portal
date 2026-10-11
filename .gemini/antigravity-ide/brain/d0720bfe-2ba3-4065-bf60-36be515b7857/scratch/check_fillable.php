<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = new App\Models\User(['role' => 'guidance_counselor']);
echo "Role: " . var_export($u->role, true) . PHP_EOL;
echo "Fillable: " . json_encode((new App\Models\User)->getFillable()) . PHP_EOL;
