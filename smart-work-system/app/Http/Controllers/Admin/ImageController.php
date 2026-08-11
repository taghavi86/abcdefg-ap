<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImageRequest;
use App\Http\Requests\Admin\BulkUploadImagesRequest;
use App\Models\Image;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Display a listing of images with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Image::query();

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $images = $query->withCount('questions')
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'))
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'images' => $images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'title' => $image->title,
                        'description' => $image->description,
                        'url' => $image->getUrlAttribute(),
                        'download_url' => $image->download_url,
                        'is_active' => $image->is_active,
                        'questions_count' => $image->questions_count,
                        'created_at' => $image->created_at->toJalaliDateTime(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $images->currentPage(),
                    'last_page' => $images->lastPage(),
                    'per_page' => $images->perPage(),
                    'total' => $images->total(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created image
     */
    public function store(StoreImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Upload image to download host (configured disk)
        $imagePath = $request->file('image')->store('images', config('filesystems.download_disk', 'public'));

        $image = Image::create([
            'url' => $imagePath,
            'download_url' => Storage::disk(config('filesystems.download_disk', 'public'))->url($imagePath),
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تصویر با موفقیت بارگذاری شد',
            'data' => [
                'image' => [
                    'id' => $image->id,
                    'url' => $image->getUrlAttribute(),
                    'download_url' => $image->download_url,
                ],
            ],
        ], 201);
    }

    /**
     * Bulk upload images (up to 300 at once)
     */
    public function bulkUpload(BulkUploadImagesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        DB::beginTransaction();
        
        try {
            $uploadedImages = [];
            $questionsPerImage = $validated['questions_per_image'];

            foreach ($request->file('images') as $index => $imageFile) {
                // Upload image
                $imagePath = $imageFile->store('images/bulk', config('filesystems.download_disk', 'public'));
                
                $image = Image::create([
                    'url' => $imagePath,
                    'download_url' => Storage::disk(config('filesystems.download_disk', 'public'))->url($imagePath),
                    'title' => "تصویر شماره " . ($index + 1),
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                // Create default questions for this image
                for ($i = 0; $i < $questionsPerImage; $i++) {
                    Question::create([
                        'image_id' => $image->id,
                        'text' => 'سوال ' . ($i + 1) . ' برای تصویر ' . $image->id,
                        'option_a' => 'گزینه A',
                        'option_b' => 'گزینه B',
                        'option_c' => 'گزینه C',
                        'option_d' => 'گزینه D',
                        'correct_option' => 'A', // Default, admin can edit later
                        'is_assessment' => false,
                        'is_active' => true,
                    ]);
                }

                $uploadedImages[] = [
                    'id' => $image->id,
                    'url' => $image->getUrlAttribute(),
                    'questions_count' => $questionsPerImage,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$questionsPerImage} سوال برای " . count($uploadedImages) . " تصویر با موفقیت ایجاد شد",
                'data' => [
                    'uploaded_count' => count($uploadedImages),
                    'total_questions_created' => count($uploadedImages) * $questionsPerImage,
                    'images' => $uploadedImages,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در بارگذاری تصاویر رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified image
     */
    public function update(StoreImageRequest $request, Image $image): JsonResponse
    {
        $validated = $request->validated();

        // Handle new image upload if provided
        if ($request->hasFile('image')) {
            // Delete old image
            if ($image->url) {
                Storage::disk(config('filesystems.download_disk', 'public'))->delete($image->url);
            }
            
            $imagePath = $request->file('image')->store('images', config('filesystems.download_disk', 'public'));
            $validated['url'] = $imagePath;
            $validated['download_url'] = Storage::disk(config('filesystems.download_disk', 'public'))->url($imagePath);
        }

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تصویر با موفقیت به‌روزرسانی شد',
            'data' => [
                'image' => [
                    'id' => $image->id,
                    'url' => $image->getUrlAttribute(),
                    'download_url' => $image->download_url,
                ],
            ],
        ]);
    }

    /**
     * Remove the specified image
     */
    public function destroy(Image $image): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            // Delete associated questions
            $image->questions()->delete();
            
            // Delete image file
            if ($image->url) {
                Storage::disk(config('filesystems.download_disk', 'public'))->delete($image->url);
            }
            
            // Delete image record
            $image->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تصویر و سوالات مرتبط با موفقیت حذف شدند',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطایی در حذف تصویر رخ داد',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Toggle image active status
     */
    public function toggleStatus(Image $image): JsonResponse
    {
        $image->update(['is_active' => !$image->is_active]);

        return response()->json([
            'success' => true,
            'message' => $image->is_active ? 'تصویر فعال شد' : 'تصویر غیرفعال شد',
            'data' => [
                'is_active' => $image->is_active,
            ],
        ]);
    }
}
