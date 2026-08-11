<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponseReviewController extends Controller
{
    /**
     * Get pending responses for review
     */
    public function getPending(Request $request): JsonResponse
    {
        $query = UserResponse::query()
            ->where('is_assessment', false)
            ->where('status', 'pending');

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $responses = $query->with(['user:id,name,family,level_id', 'question.image:id,url'])
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'responses' => $responses->map(function ($response) {
                    return [
                        'id' => $response->id,
                        'user' => [
                            'id' => $response->user->id,
                            'name' => $response->user->name . ' ' . $response->user->family,
                            'level' => $response->user->level ? $response->user->level->name : null,
                        ],
                        'question' => [
                            'id' => $response->question->id,
                            'text' => $response->question->text,
                            'image_url' => $response->question->image ? $response->question->image->getUrlAttribute() : null,
                        ],
                        'selected_option' => $response->selected_option,
                        'is_correct' => $response->is_correct,
                        'score_earned' => $response->score_earned,
                        'response_time' => $response->response_time,
                        'created_at' => $response->created_at->toJalaliDateTime(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $responses->currentPage(),
                    'last_page' => $responses->lastPage(),
                    'total' => $responses->total(),
                ],
            ],
        ]);
    }

    /**
     * Approve a response
     */
    public function approve(UserResponse $response): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $response->update(['status' => 'approved']);
            
            // Add to user's verified coins
            $user = $response->user;
            $user->verified_coins += $response->score_earned;
            $user->save();

            // Check for achievements
            $this->checkAndAwardAchievements($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پاسخ با موفقیت تایید شد',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در تایید پاسخ رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reject a response
     */
    public function reject(Request $request, UserResponse $response): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $validated = $request->validate([
                'rejection_reason' => ['nullable', 'string', 'max:500'],
            ]);

            $response->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);
            
            // Deduct from user's total coins (since they were added but not verified)
            $user = $response->user;
            $user->total_coins -= $response->score_earned;
            $user->save();

            // Check if user should be demoted due to too many rejections
            $this->checkUserDemotion($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'پاسخ رد شد',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در رد پاسخ رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk approve responses
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'response_ids' => ['required', 'array', 'min:1'],
            'response_ids.*' => ['integer', 'exists:user_responses,id'],
        ]);

        DB::beginTransaction();
        
        try {
            $count = 0;
            foreach ($validated['response_ids'] as $responseId) {
                $response = UserResponse::findOrFail($responseId);
                
                if ($response->status === 'pending') {
                    $response->update(['status' => 'approved']);
                    
                    $user = $response->user;
                    $user->verified_coins += $response->score_earned;
                    $user->save();
                    
                    $count++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$count} پاسخ با موفقیت تایید شدند",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در تایید پاسخ‌ها رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get review statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'pending_count' => UserResponse::where('is_assessment', false)
                ->where('status', 'pending')
                ->count(),
            'approved_today' => UserResponse::where('is_assessment', false)
                ->where('status', 'approved')
                ->whereDate('created_at', today())
                ->count(),
            'rejected_today' => UserResponse::where('is_assessment', false)
                ->where('status', 'rejected')
                ->whereDate('created_at', today())
                ->count(),
            'total_pending_score' => UserResponse::where('is_assessment', false)
                ->where('status', 'pending')
                ->sum('score_earned'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Check and award achievements to user
     */
    private function checkAndAwardAchievements(User $user)
    {
        // This would check for various achievement conditions
        // For now, it's a placeholder for future implementation
    }

    /**
     * Check if user should be demoted due to rejections
     */
    private function checkUserDemotion(User $user)
    {
        // Count recent rejections
        $recentRejections = UserResponse::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // If more than 5 rejections in a week, consider demotion
        if ($recentRejections >= 5 && $user->level_id) {
            // Demote logic would go here
            // For now, just a placeholder
        }
    }
}
