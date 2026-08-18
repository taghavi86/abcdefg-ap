<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workbench\SubmitWorkResponseRequest;
use App\Models\Image;
use App\Models\Question;
use App\Models\UserResponse;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkbenchController extends Controller
{
    /**
     * Get available work items (images with questions) for the user
     */
    public function getAvailableTasks(): JsonResponse
    {
        $user = auth()->user();

        // Check if user has a level (must complete assessment first)
        if (!$user->level_id) {
            return response()->json([
                'success' => false,
                'message' => 'لطفاً ابتدا در آزمون تعیین سطح شرکت کنید',
            ], 403);
        }

        // Get images that haven't been answered by this user yet
        $answeredImageIds = UserResponse::where('user_id', $user->id)
            ->where('is_level_test', false)
            ->join('questions', 'user_responses.question_id', '=', 'questions.id')
            ->pluck('questions.image_id');

        // Get available images with their questions (workbench type only)
        $tasks = Image::where('is_active', true)
            ->whereNotIn('id', $answeredImageIds)
            ->with(['questions' => function ($query) {
                $query->where('is_active', true)
                    ->where('type', 'workbench')
                    ->orderBy('order_index')
                    ->limit(3); // Max 3 questions per image as per spec
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10) // Return 10 tasks at a time
            ->get(['id', 'image_path', 'download_host_url', 'title']);

        // Format response
        $formattedTasks = $tasks->map(function ($image) {
            return [
                'image' => [
                    'id' => $image->id,
                    'url' => $image->getFullImageUrlAttribute(),
                    'title' => $image->title,
                ],
                'questions' => $image->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'answer_type' => 'numeric', // Only numeric answers for workbench
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $formattedTasks,
                'total_available' => Image::where('is_active', true)
                    ->whereNotIn('id', $answeredImageIds)
                    ->count(),
            ],
        ]);
    }

    /**
     * Submit a work response
     */
    public function submitWork(SubmitWorkResponseRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Check if user has a level
        if (!$user->level_id) {
            return response()->json([
                'success' => false,
                'message' => 'لطفاً ابتدا در آزمون تعیین سطح شرکت کنید',
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            $question = Question::findOrFail($validated['question_id']);
            
            // For workbench: no correct answer required (just store the numeric answer)
            // Admin/support will review manually
            $userAnswer = (string)$validated['user_answer'];
            
            // Calculate speed score based on response time
            $speedScore = $this->calculateSpeedScore($validated['response_time']);
            
            // For workbench, accuracy is always 0 (will be reviewed by support)
            $accuracyScore = 0;
            
            // Base score (only speed for now, accuracy will be determined by support)
            $baseScore = $speedScore;

            // Apply income multiplier based on user level
            $multiplier = $user->level->income_multiplier ?? 1;
            $finalScore = $baseScore * $multiplier;

            // Check if there's an active challenge (double coins)
            $activeChallenge = Challenge::where('is_active', true)
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->first();

            if ($activeChallenge && $activeChallenge->bonus_multiplier) {
                $finalScore *= $activeChallenge->bonus_multiplier;
            }

            // Store response (pending approval by support)
            $userResponse = UserResponse::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'image_id' => $question->image_id,
                'user_answer' => $userAnswer,
                'is_correct' => null, // Will be set by support during review
                'response_time_seconds' => $validated['response_time'],
                'accuracy_score_earned' => 0, // Will be set by support
                'speed_score_earned' => $speedScore,
                'total_coins_earned' => $finalScore,
                'type' => 'workbench',
                'is_level_test' => false,
                'status' => 'pending', // Requires admin/support approval
            ]);

            // Update user's total coins (but not verified until approved)
            $user->total_coins += $finalScore;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پاسخ شما با موفقیت ثبت شد و پس از بررسی تایید می‌شود',
                'data' => [
                    'response_id' => $userResponse->id,
                    'user_answer' => $userAnswer,
                    'speed_score' => $speedScore,
                    'multiplier' => $multiplier,
                    'challenge_bonus' => $activeChallenge ? $activeChallenge->bonus_multiplier : null,
                    'final_score' => $finalScore,
                    'status' => $userResponse->status,
                    'total_coins' => $user->total_coins,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در ثبت پاسخ رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get user's work history
     */
    public function getHistory(): JsonResponse
    {
        $user = auth()->user();

        $responses = UserResponse::where('user_id', $user->id)
            ->where('is_level_test', false)
            ->with(['question.image:id,image_path,download_host_url'])
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'responses' => $responses->map(function ($response) {
                    return [
                        'id' => $response->id,
                        'image' => $response->question->image ? [
                            'id' => $response->question->image->id,
                            'url' => $response->question->image->getFullImageUrlAttribute(),
                        ] : null,
                        'question_text' => $response->question->question_text ?? '',
                        'user_answer' => $response->user_answer,
                        'is_correct' => $response->is_correct,
                        'speed_score_earned' => $response->speed_score_earned,
                        'total_coins_earned' => $response->total_coins_earned,
                        'status' => $response->status,
                        'response_time_seconds' => $response->response_time_seconds,
                        'created_at' => $response->created_at->toJalaliDateTime(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $responses->currentPage(),
                    'last_page' => $responses->lastPage(),
                    'per_page' => $responses->perPage(),
                    'total' => $responses->total(),
                ],
            ],
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'total_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_level_test', false)
                ->count(),
            'approved_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_level_test', false)
                ->where('status', 'approved')
                ->count(),
            'pending_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_level_test', false)
                ->where('status', 'pending')
                ->count(),
            'rejected_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_level_test', false)
                ->where('status', 'rejected')
                ->count(),
            'total_coins' => $user->total_coins,
            'verified_coins' => $user->verified_coins,
            'accuracy_rate' => 0,
        ];

        // Calculate accuracy rate
        $totalAnswered = $stats['approved_responses'] + $stats['rejected_responses'];
        if ($totalAnswered > 0) {
            $correctAnswers = UserResponse::where('user_id', $user->id)
                ->where('is_level_test', false)
                ->where('status', 'approved')
                ->where('is_correct', true)
                ->count();
            
            $stats['accuracy_rate'] = round(($correctAnswers / $totalAnswered) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate speed score based on response time
     * < 2 seconds: 5 coins
     * 3-6 seconds: 3 coins
     * 6-10 seconds: 1 coin
     * > 10 seconds: 0 coins
     */
    private function calculateSpeedScore(float $responseTime): int
    {
        if ($responseTime < 2) {
            return 5;
        } elseif ($responseTime <= 6) {
            return 3;
        } elseif ($responseTime <= 10) {
            return 1;
        }
        
        return 0;
    }
}
