<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'ReadsNews Backend API',
        'status' => 'active',
        'version' => '1.0.0',
    ]);
});
