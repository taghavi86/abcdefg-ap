<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserResponse;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserResponsesExport;

class ResponseReviewController extends Controller
{
    /**
     * Get all responses with filters (both workbench and level_test)
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserResponse::query()
            ->with(['user:id,name,family,email', 'question:id,question_text,image_id,correct_answer', 'image:id,title,image_path']);

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('image_id')) {
            $query->where('image_id', $request->image_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_level_test')) {
            $query->where('is_level_test', $request->boolean('is_level_test'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('family', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $responses = $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'))
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'responses' => $responses->map(function ($response) {
                    return [
                        'id' => $response->id,
                        'user' => [
                            'id' => $response->user->id,
                            'name' => $response->user->name ?? '',
                            'family' => $response->user->family ?? '',
                            'email' => $response->user->email ?? '',
                        ],
                        'image' => [
                            'id' => $response->image_id,
                            'title' => $response->image->title ?? '',
                            'image_path' => $response->image->image_path ?? '',
                        ],
                        'question' => [
                            'id' => $response->question_id,
                            'text' => $response->question->question_text ?? '',
                            'correct_answer' => $response->question->correct_answer ?? '',
                        ],
                        'user_answer' => $response->user_answer,
                        'is_correct' => $response->is_correct,
                        'response_time_seconds' => $response->response_time_seconds,
                        'accuracy_score_earned' => $response->accuracy_score_earned,
                        'speed_score_earned' => $response->speed_score_earned,
                        'total_coins_earned' => $response->total_coins_earned,
                        'type' => $response->type,
                        'is_level_test' => $response->is_level_test,
                        'status' => $response->status,
                        'is_approved' => $response->is_approved,
                        'admin_notes' => $response->admin_notes,
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
     * Export filtered responses to Excel
     */
    public function export(Request $request)
    {
        $query = UserResponse::query();

        // Apply same filters as index
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('image_id')) {
            $query->where('image_id', $request->image_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_level_test')) {
            $query->where('is_level_test', $request->boolean('is_level_test'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = "responses_export_" . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new UserResponsesExport($query), $filename);
    }

    /**
     * Get pending responses for review
     */
    public function getPending(Request $request): JsonResponse
    {
        $query = UserResponse::query()
            ->where('status', 'pending');

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('image_id')) {
            $query->where('image_id', $request->image_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $responses = $query->with(['user:id,name,family,level_id', 'question.image:id,image_path', 'image:id,title,image_path'])
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
                        'image' => [
                            'id' => $response->image_id,
                            'title' => $response->image->title ?? '',
                            'image_path' => $response->image->image_path ?? '',
                        ],
                        'question' => [
                            'id' => $response->question_id,
                            'text' => $response->question->question_text ?? '',
                        ],
                        'user_answer' => $response->user_answer,
                        'response_time' => $response->response_time_seconds,
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
            $response->update([
                'status' => 'approved',
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            // Add to user's verified coins
            $user = $response->user;
            $user->verified_coins += $response->total_coins_earned;
            $user->save();

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
                'admin_notes' => $validated['rejection_reason'] ?? null,
            ]);

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
                    $response->update([
                        'status' => 'approved',
                        'is_approved' => true,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    
                    $user = $response->user;
                    $user->verified_coins += $response->total_coins_earned;
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
            'pending_count' => UserResponse::where('status', 'pending')->count(),
            'approved_today' => UserResponse::where('status', 'approved')
                ->whereDate('created_at', today())
                ->count(),
            'rejected_today' => UserResponse::where('status', 'rejected')
                ->whereDate('created_at', today())
                ->count(),
            'total_pending_score' => UserResponse::where('status', 'pending')
                ->sum('total_coins_earned'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
