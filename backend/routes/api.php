<?php

use App\Http\Controllers\Admin\ArticlesController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\SourcesController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Categories (cho trang đọc tin tức)
Route::get('/categories', [CategoriesController::class, 'index']);

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

// Quản trị hệ thống (Admin & Editor)
Route::middleware(['auth:sanctum', 'role:admin,editor'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['success' => true, 'message' => 'Chào mừng đến khu vực quản trị!']);
    });

    // Quản lý danh mục
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoriesController::class, 'index']);
        Route::post('/', [CategoriesController::class, 'store']);
        Route::get('/{id}', [CategoriesController::class, 'show']);
        Route::put('/{id}', [CategoriesController::class, 'update']);
        Route::patch('/{id}/toggle-status', [CategoriesController::class, 'toggleStatus']);
        Route::delete('/{id}', [CategoriesController::class, 'destroy']);
    });

    // Quản lý người dùng (Dành riêng cho Admin)
    Route::prefix('users')->middleware('role:admin')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::patch('/{id}/role', [UserController::class, 'updateRole']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Quản lý bài báo
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticlesController::class, 'index']);
        Route::post('/', [ArticlesController::class, 'store']);
        Route::get('/{id}', [ArticlesController::class, 'show']);
        Route::post('/{id}', [ArticlesController::class, 'update']);     // POST thay PUT (multipart)
        Route::patch('/{id}/toggle-publish', [ArticlesController::class, 'togglePublish']);
        Route::delete('/{id}', [ArticlesController::class, 'destroy']);
    });

    // Quản lý nguồn tin (Sources)
    Route::prefix('sources')->group(function () {
        Route::get('/', [SourcesController::class, 'index']);
        Route::post('/{id}/crawl', [SourcesController::class, 'crawlRss']);
    });
});

Route::middleware(['auth:sanctum', 'role:editor,admin'])->prefix('editor')->group(function () {
    Route::get('/articles', function () {
        return response()->json(['success' => true, 'message' => 'Khu vực quản lý dành cho Editor & Admin.']);
    });
});
