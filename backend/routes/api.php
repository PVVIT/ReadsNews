<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Phân quyền
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['success' => true, 'message' => 'Chào mừng Admin!']);
    });

    // Quản lý người dùng
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::patch('/{id}/role', [UserController::class, 'updateRole']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'role:editor,admin'])->prefix('editor')->group(function () {
    Route::get('/articles', function () {
        return response()->json(['success' => true, 'message' => 'Khu vực quản lý dành cho Editor & Admin.']);
    });
});
