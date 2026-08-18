<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SubmitAssessmentRequest;
use App\Models\Question;
use App\Models\UserResponse;
use App\Models\UserLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Get assessment questions for the user
     * According to the spec: 4 exams, each with 3 questions = 12 total questions
     */
    public function getQuestions(): JsonResponse
    {
        $user = auth()->user();

        // Check if user already has a level
        if ($user->level_id) {
            return response()->json([
                'success' => false,
                'message' => 'شما قبلاً در آزمون تعیین سطح شرکت کرده‌اید',
                'data' => [
                    'level' => [
                        'id' => $user->level->id,
                        'name' => $user->level->name,
                        'income_multiplier' => $user->level->income_multiplier,
                    ],
                ],
            ], 403);
        }

        // Check if user can take the assessment
        if (!$user->canTakeAssessment()) {
            $lastAttempt = $user->responses()
                ->where('is_level_test', true)
                ->latest('created_at')
                ->first();
            
            $nextAvailableDate = $lastAttempt?->created_at->addDays(
                $user->responses()->where('is_level_test', true)->count() === 1 ? 1 : 3
            );

            return response()->json([
                'success' => false,
                'message' => 'برای شرکت مجدد در آزمون باید صبر کنید',
                'data' => [
                    'next_available_date' => $nextAvailableDate?->toJalaliDateTime(),
                ],
            ], 403);
        }

        // Get 3 random level_test questions
        $questions = Question::where('is_active', true)
            ->where('type', 'level_test')
            ->inRandomOrder()
            ->limit(3)
            ->get(['id', 'image_id', 'question_text', 'order_index']);

        // Load image URLs
        $questions->load(['image:id,image_path,download_host_url']);

        return response()->json([
            'success' => true,
            'data' => [
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'image_id' => $question->image_id,
                        'image_url' => $question->image->getFullImageUrlAttribute() ?? null,
                        'question_text' => $question->question_text,
                        'order_index' => $question->order_index,
                        'answer_type' => 'numeric', // Only numeric answers
                    ];
                }),
                'total_questions' => 3,
                'time_limit_per_question' => config('assessment.time_limit_per_question', 60),
            ],
        ]);
    }

    /**
     * Submit assessment responses and calculate score
     */
    public function submitAssessment(SubmitAssessmentRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Check again if user can take assessment
        if ($user->level_id && !$user->canTakeAssessment()) {
            return response()->json([
                'success' => false,
                'message' => 'شما مجاز به شرکت در این آزمون نیستید',
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            $totalScore = 0;
            $totalCoins = 0;
            $responsesData = [];

            foreach ($validated['responses'] as $response) {
                $question = Question::findOrFail($response['question_id']);
                
                // For level_test, compare numeric answer
                $isCorrect = strtolower(trim((string)$response['user_answer'])) === strtolower(trim((string)$question->correct_answer));
                $accuracyScore = $isCorrect ? 5 : 0;

                // Calculate speed score based on response time
                $responseTime = $response['response_time'];
                $speedScore = $this->calculateSpeedScore($responseTime);

                // Total score for this question (max 10)
                $questionScore = $accuracyScore + $speedScore;
                
                $totalScore += $questionScore;
                $totalCoins += $questionScore;

                // Store response
                $userResponse = UserResponse::create([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                    'image_id' => $question->image_id,
                    'user_answer' => (string)$response['user_answer'],
                    'is_correct' => $isCorrect,
                    'response_time_seconds' => $responseTime,
                    'accuracy_score_earned' => $accuracyScore,
                    'speed_score_earned' => $speedScore,
                    'total_coins_earned' => $questionScore,
                    'type' => 'level_test',
                    'is_level_test' => true,
                    'status' => 'approved', // Assessment responses are auto-approved
                ]);

                $responsesData[] = [
                    'question_id' => $question->id,
                    'image_id' => $question->image_id,
                    'user_answer' => $userResponse->user_answer,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                    'score_earned' => $questionScore,
                ];
            }

            // Update user's current score
            $user->current_score = max($user->current_score, $totalScore);
            $user->total_coins += $totalCoins;
            $user->verified_coins += $totalCoins; // Auto-approved for level test
            $user->save();

            // Update user level based on score
            $user->updateLevelBasedOnScore();
            $user->load('level');

            DB::commit();

            // Prepare response message based on level
            $message = 'آزمون تعیین سطح با موفقیت ثبت شد';
            if ($user->level) {
                $message .= ". سطح شما: {$user->level->name}";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'total_score' => $totalScore,
                    'total_coins_earned' => $totalCoins,
                    'responses' => $responsesData,
                    'user' => [
                        'current_score' => $user->current_score,
                        'total_coins' => $user->total_coins,
                        'verified_coins' => $user->verified_coins,
                        'level' => $user->level ? [
                            'id' => $user->level->id,
                            'name' => $user->level->name,
                            'income_multiplier' => $user->level->income_multiplier,
                        ] : null,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در ثبت پاسخ‌ها رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
