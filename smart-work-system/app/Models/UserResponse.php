<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'image_id',
        'user_answer',
        'is_correct',
        'response_time_seconds',
        'accuracy_score_earned',
        'speed_score_earned',
        'total_coins_earned',
        'type',
        'is_level_test',
        'test_attempt_number',
        'is_approved',
        'approved_by',
        'approved_at',
        'admin_notes',
        'status',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'response_time_seconds' => 'integer',
        'accuracy_score_earned' => 'integer',
        'speed_score_earned' => 'integer',
        'total_coins_earned' => 'integer',
        'is_level_test' => 'boolean',
        'test_attempt_number' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationship: Response belongs to user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Response belongs to question
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Relationship: Response belongs to image
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Relationship: Response approved by user
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Level test responses only
     */
    public function scopeLevelTest($query)
    {
        return $query->where('is_level_test', true);
    }

    /**
     * Scope: Workbench responses only
     */
    public function scopeWorkbench($query)
    {
        return $query->where('is_level_test', false);
    }

    /**
     * Scope: Pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved responses
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Calculate speed score based on response time
     * < 2 seconds: 5 coins
     * 2-6 seconds: 3 coins
     * 6-10 seconds: 1 coin
     * > 10 seconds: 0 coins
     */
    public static function calculateSpeedScore(int $seconds): int
    {
        if ($seconds < 2) {
            return 5;
        } elseif ($seconds <= 6) {
            return 3;
        } elseif ($seconds <= 10) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Calculate total coins for a response
     */
    public static function calculateTotalCoins(bool $isCorrect, int $responseTimeSeconds): int
    {
        $accuracyScore = $isCorrect ? 5 : 0;
        $speedScore = self::calculateSpeedScore($responseTimeSeconds);
        return min($accuracyScore + $speedScore, 10); // Max 10 coins per question
    }

    /**
     * Get speed category label
     */
    public function getSpeedCategoryAttribute(): string
    {
        if ($this->response_time_seconds < 2) {
            return 'عالی (کمتر از ۲ ثانیه)';
        } elseif ($this->response_time_seconds <= 6) {
            return 'خوب (۳ تا ۶ ثانیه)';
        } elseif ($this->response_time_seconds <= 10) {
            return 'متوسط (۶ تا ۱۰ ثانیه)';
        } else {
            return 'کند (بیشتر از ۱۰ ثانیه)';
        }
    }
}
