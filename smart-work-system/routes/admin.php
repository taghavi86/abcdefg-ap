<?php

use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\ResponseReviewController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Image Management
    Route::prefix('images')->group(function () {
        Route::get('/', [ImageController::class, 'index']);
        Route::post('/', [ImageController::class, 'store']);
        Route::post('/bulk-upload', [ImageController::class, 'bulkUpload']);
        Route::put('/{image}', [ImageController::class, 'update']);
        Route::delete('/{image}', [ImageController::class, 'destroy']);
        Route::post('/{image}/toggle-status', [ImageController::class, 'toggleStatus']);
    });

    // Question Management (placeholder - would be similar to ImageController)
    Route::prefix('questions')->group(function () {
        // Routes would be added when QuestionController is created
    });

    // Response Review
    Route::prefix('responses')->group(function () {
        Route::get('/pending', [ResponseReviewController::class, 'getPending']);
        Route::post('/{response}/approve', [ResponseReviewController::class, 'approve']);
        Route::post('/{response}/reject', [ResponseReviewController::class, 'reject']);
        Route::post('/bulk-approve', [ResponseReviewController::class, 'bulkApprove']);
        Route::get('/statistics', [ResponseReviewController::class, 'getStatistics']);
    });

    // User Management (placeholder)
    Route::prefix('users')->group(function () {
        // Routes would be added when UserController is created
    });

    // Challenges & Gamification Management (placeholder)
    Route::prefix('challenges')->group(function () {
        // Routes would be added
    });

    Route::prefix('rankings')->group(function () {
        // Routes would be added
    });
});
