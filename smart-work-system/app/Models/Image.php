<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'image_path',
        'image_url',
        'download_host_url',
        'type',
        'question_count',
        'is_active',
        'is_bulk_import',
        'bulk_import_batch_id',
        'created_by',
    ];

    protected $casts = [
        'question_count' => 'integer',
        'is_active' => 'boolean',
        'is_bulk_import' => 'boolean',
    ];

    /**
     * Relationship: Image has many questions
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Relationship: Image created by user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Image has many user responses
     */
    public function userResponses(): HasMany
    {
        return $this->hasMany(UserResponse::class);
    }

    /**
     * Scope: Active images only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Bulk import batch
     */
    public function scopeBatch($query, $batchId)
    {
        return $query->where('bulk_import_batch_id', $batchId);
    }

    /**
     * Get full image URL (from download host if available)
     */
    public function getFullImageUrlAttribute()
    {
        return $this->download_host_url ?? $this->image_url ?? asset('storage/' . $this->image_path);
    }

    /**
     * Generate unique batch ID for bulk import
     */
    public static function generateBatchId()
    {
        return 'BATCH_' . strtoupper(uniqid()) . '_' . time();
    }
}
