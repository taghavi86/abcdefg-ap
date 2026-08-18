<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuestionsExport;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Question::query()->with(['image:id,title,image_path', 'creator:id,name,family']);

        // Filters
        if ($request->has('image_id')) {
            $query->where('image_id', $request->image_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('question_text', 'like', "%{$search}%");
        }

        $questions = $query->orderBy($request->get('sort_by', 'order_index'), $request->get('sort_order', 'asc'))
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'image_id' => $question->image_id,
                        'image_title' => $question->image->title ?? null,
                        'image_path' => $question->image->image_path ?? null,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'correct_answer' => $question->correct_answer,
                        'order_index' => $question->order_index,
                        'is_active' => $question->is_active,
                        'created_by_name' => $question->creator->name ?? null,
                        'created_at' => $question->created_at->toJalaliDateTime(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'last_page' => $questions->lastPage(),
                    'per_page' => $questions->perPage(),
                    'total' => $questions->total(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created question
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => ['required', 'integer', 'exists:images,id'],
            'question_text' => ['required', 'string', 'max:1000'],
            'type' => ['required', 'in:workbench,level_test'],
            'correct_answer' => ['nullable', 'string', 'max:100'], // Required for level_test
            'order_index' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // For level_test, correct_answer is required
        if ($validated['type'] === 'level_test' && empty($validated['correct_answer'])) {
            return response()->json([
                'success' => false,
                'message' => 'برای سوالات تعیین سطح، وارد کردن جواب صحیح الزامی است',
            ], 422);
        }

        $question = Question::create([
            'image_id' => $validated['image_id'],
            'question_text' => $validated['question_text'],
            'type' => $validated['type'],
            'correct_answer' => $validated['correct_answer'] ?? null,
            'order_index' => $validated['order_index'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت ایجاد شد',
            'data' => [
                'question' => [
                    'id' => $question->id,
                    'image_id' => $question->image_id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'correct_answer' => $question->correct_answer,
                    'order_index' => $question->order_index,
                    'is_active' => $question->is_active,
                ],
            ],
        ], 201);
    }

    /**
     * Update the specified question
     */
    public function update(Request $request, Question $question): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => ['sometimes', 'integer', 'exists:images,id'],
            'question_text' => ['sometimes', 'string', 'max:1000'],
            'type' => ['sometimes', 'in:workbench,level_test'],
            'correct_answer' => ['nullable', 'string', 'max:100'],
            'order_index' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // For level_test, correct_answer is required
        if (isset($validated['type']) && $validated['type'] === 'level_test' && empty($validated['correct_answer'])) {
            return response()->json([
                'success' => false,
                'message' => 'برای سوالات تعیین سطح، وارد کردن جواب صحیح الزامی است',
            ], 422);
        }

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت به‌روزرسانی شد',
            'data' => [
                'question' => [
                    'id' => $question->id,
                    'image_id' => $question->image_id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'correct_answer' => $question->correct_answer,
                    'order_index' => $question->order_index,
                    'is_active' => $question->is_active,
                ],
            ],
        ]);
    }

    /**
     * Remove the specified question
     */
    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت حذف شد',
        ]);
    }

    /**
     * Get questions by image ID
     */
    public function getByImage(Image $image): JsonResponse
    {
        $questions = $image->questions()
            ->orderBy('order_index')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'image' => [
                    'id' => $image->id,
                    'title' => $image->title,
                    'image_path' => $image->image_path,
                ],
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'correct_answer' => $question->correct_answer,
                        'order_index' => $question->order_index,
                        'is_active' => $question->is_active,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Export questions to Excel by image ID
     */
    public function exportByImage(Image $image)
    {
        $filename = "questions_image_{$image->id}_" . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new QuestionsExport($image), $filename);
    }

    /**
     * Toggle question active status
     */
    public function toggleStatus(Question $question): JsonResponse
    {
        $question->update(['is_active' => !$question->is_active]);

        return response()->json([
            'success' => true,
            'message' => $question->is_active ? 'سوال فعال شد' : 'سوال غیرفعال شد',
            'data' => [
                'is_active' => $question->is_active,
            ],
        ]);
    }
}
