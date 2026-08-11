<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'image_id',
        'question_text',
        'type',
        'options',
        'correct_answer',
        'accuracy_score',
        'speed_score',
        'total_score',
        'order_index',
        'is_control_question',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'accuracy_score' => 'integer',
        'speed_score' => 'integer',
        'total_score' => 'integer',
        'order_index' => 'integer',
        'is_control_question' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Question belongs to image
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Relationship: Question has many user responses
     */
    public function userResponses(): HasMany
    {
        return $this->hasMany(UserResponse::class);
    }

    /**
     * Relationship: Question created by user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active questions only
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
     * Scope: Control questions only
     */
    public function scopeControlQuestions($query)
    {
        return $query->where('is_control_question', true);
    }

    /**
     * Scope: Order by index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Calculate speed score based on response time
     * < 2 seconds: 5 coins
     * 2-6 seconds: 3 coins
     * 6-10 seconds: 1 coin
     * > 10 seconds: 0 coins
     */
    public static function calculateSpeedScore(int $responseTimeSeconds): int
    {
        if ($responseTimeSeconds < 2) {
            return 5;
        } elseif ($responseTimeSeconds <= 6) {
            return 3;
        } elseif ($responseTimeSeconds <= 10) {
            return 1;
        }
        
        return 0;
    }

    /**
     * Calculate total score for a response
     */
    public static function calculateTotalScore(bool $isCorrect, int $responseTimeSeconds): int
    {
        $accuracyScore = $isCorrect ? 5 : 0;
        $speedScore = self::calculateSpeedScore($responseTimeSeconds);
        
        return min($accuracyScore + $speedScore, 10); // Max 10 coins per question
    }
}
