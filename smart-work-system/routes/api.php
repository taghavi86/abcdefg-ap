<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\WorkbenchController;
use App\Http\Controllers\Api\GamificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/profile', [AuthController::class, 'profile']);

        // Assessment routes (Level determination)
        Route::prefix('assessment')->group(function () {
            Route::get('/questions', [AssessmentController::class, 'getQuestions']);
            Route::post('/submit', [AssessmentController::class, 'submitAssessment']);
        });

        // Workbench routes (Smart work desk)
        Route::prefix('workbench')->group(function () {
            Route::get('/tasks', [WorkbenchController::class, 'getAvailableTasks']);
            Route::post('/submit', [WorkbenchController::class, 'submitWork']);
            Route::get('/history', [WorkbenchController::class, 'getHistory']);
            Route::get('/statistics', [WorkbenchController::class, 'getStatistics']);
        });

        // Gamification routes
        Route::prefix('gamification')->group(function () {
            Route::get('/achievements', [GamificationController::class, 'getAchievements']);
            Route::get('/rankings', [GamificationController::class, 'getWeeklyRankings']);
            Route::get('/challenges', [GamificationController::class, 'getChallenges']);
            Route::get('/message', [GamificationController::class, 'getMotivationalMessage']);
            Route::get('/referrals', [GamificationController::class, 'getReferralInfo']);
        });
    });
});
