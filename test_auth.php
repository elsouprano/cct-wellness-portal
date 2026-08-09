<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::find(5); // Student
if (!$user) {
    echo "User 5 not found.\n";
    exit;
}

$request = Illuminate\Http\Request::create('/manage/announcements/create', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Since we are mocking the request, we need to bypass full auth middleware or simulate it.
// Simpler: run an artisan test!
