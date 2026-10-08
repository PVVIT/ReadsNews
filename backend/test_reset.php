<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'vanvu31205@gmail.com')->first();
$token = Illuminate\Support\Facades\Password::broker()->createToken($user);
echo "TOKEN: " . $token . PHP_EOL;

