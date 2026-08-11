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
                ->where('is_assessment', true)
                ->latest('created_at')
                ->first();
            
            $nextAvailableDate = $lastAttempt->created_at->addDays(
                $user->responses()->where('is_assessment', true)->count() === 1 ? 1 : 3
            );

            return response()->json([
                'success' => false,
                'message' => 'برای شرکت مجدد در آزمون باید صبر کنید',
                'data' => [
                    'next_available_date' => $nextAvailableDate->toJalaliDateTime(),
                ],
            ], 403);
        }

        // Get 3 random questions for assessment
        // In a real scenario, you might want to track which exam number (1-4) this is
        $questions = Question::where('is_active', true)
            ->inRandomOrder()
            ->limit(3)
            ->get(['id', 'text', 'option_a', 'option_b', 'option_c', 'option_d', 'image_id']);

        // Load image URLs if questions have images
        $questions->load(['image:id,url,download_url']);

        return response()->json([
            'success' => true,
            'data' => [
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'text' => $question->text,
                        'options' => [
                            'A' => $question->option_a,
                            'B' => $question->option_b,
                            'C' => $question->option_c,
                            'D' => $question->option_d,
                        ],
                        'image' => $question->image ? [
                            'url' => $question->image->getUrlAttribute(),
                            'download_url' => $question->image->download_url,
                        ] : null,
                    ];
                }),
                'total_questions' => 3,
                'time_limit_per_question' => config('assessment.time_limit_per_question', 60), // 60 seconds default
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
                
                // Calculate accuracy score (max 5 coins)
                $isCorrect = $question->correct_option === $response['selected_option'];
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
                    'selected_option' => $response['selected_option'],
                    'is_correct' => $isCorrect,
                    'response_time' => $responseTime,
                    'score_earned' => $questionScore,
                    'accuracy_score' => $accuracyScore,
                    'speed_score' => $speedScore,
                    'is_assessment' => true,
                    'status' => 'approved', // Assessment responses are auto-approved
                ]);

                $responsesData[] = [
                    'question_id' => $question->id,
                    'is_correct' => $isCorrect,
                    'score_earned' => $questionScore,
                ];
            }

            // Update user's current score
            // Note: We might want to keep track of best score or average
            $user->current_score = max($user->current_score, $totalScore);
            $user->total_coins += $totalCoins;
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
