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
            ->where('is_assessment', false)
            ->join('questions', 'user_responses.question_id', '=', 'questions.id')
            ->pluck('questions.image_id');

        // Get available images with their questions
        $tasks = Image::where('is_active', true)
            ->whereNotIn('id', $answeredImageIds)
            ->with(['questions' => function ($query) {
                $query->where('is_active', true)
                    ->limit(3); // Max 3 questions per image as per spec
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10) // Return 10 tasks at a time
            ->get(['id', 'url', 'download_url', 'title']);

        // Format response
        $formattedTasks = $tasks->map(function ($image) {
            return [
                'image' => [
                    'id' => $image->id,
                    'url' => $image->getUrlAttribute(),
                    'download_url' => $image->download_url,
                    'title' => $image->title,
                ],
                'questions' => $image->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'text' => $question->text,
                        'options' => [
                            'A' => $question->option_a,
                            'B' => $question->option_b,
                            'C' => $question->option_c,
                            'D' => $question->option_d,
                        ],
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
            
            // Calculate accuracy score (max 5 coins)
            $isCorrect = $question->correct_option === $validated['selected_option'];
            $accuracyScore = $isCorrect ? 5 : 0;

            // Calculate speed score based on response time
            $speedScore = $this->calculateSpeedScore($validated['response_time']);

            // Total score for this question (max 10)
            $baseScore = $accuracyScore + $speedScore;

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

            // Store response (pending approval)
            $userResponse = UserResponse::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'selected_option' => $validated['selected_option'],
                'is_correct' => $isCorrect,
                'response_time' => $validated['response_time'],
                'score_earned' => $finalScore,
                'accuracy_score' => $accuracyScore,
                'speed_score' => $speedScore,
                'is_assessment' => false,
                'status' => 'pending', // Requires admin approval
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
                    'is_correct' => $isCorrect,
                    'base_score' => $baseScore,
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
            ->where('is_assessment', false)
            ->with(['question.image:id,url,download_url'])
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'responses' => $responses->map(function ($response) {
                    return [
                        'id' => $response->id,
                        'question_text' => $response->question->text,
                        'image' => $response->question->image ? [
                            'url' => $response->question->image->getUrlAttribute(),
                            'download_url' => $response->question->image->download_url,
                        ] : null,
                        'selected_option' => $response->selected_option,
                        'is_correct' => $response->is_correct,
                        'score_earned' => $response->score_earned,
                        'status' => $response->status,
                        'response_time' => $response->response_time,
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
                ->where('is_assessment', false)
                ->count(),
            'approved_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_assessment', false)
                ->where('status', 'approved')
                ->count(),
            'pending_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_assessment', false)
                ->where('status', 'pending')
                ->count(),
            'rejected_responses' => UserResponse::where('user_id', $user->id)
                ->where('is_assessment', false)
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
                ->where('is_assessment', false)
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
