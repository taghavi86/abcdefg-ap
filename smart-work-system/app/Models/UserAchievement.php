<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'badge_name',
        'badge_description',
        'badge_icon',
        'type',
        'requirement_value',
        'is_claimed',
        'earned_at',
    ];

    protected $casts = [
        'requirement_value' => 'integer',
        'is_claimed' => 'boolean',
        'earned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Available badge types
     */
    public static function getBadgeTypes(): array
    {
        return [
            'milestone' => 'Milestone',
            'speed' => 'Speed',
            'accuracy' => 'Accuracy',
            'weekly_rank' => 'Weekly Rank',
            'referral' => 'Referral',
        ];
    }

    /**
     * Predefined badges
     */
    public static function getPredefinedBadges(): array
    {
        return [
            [
                'name' => 'First Steps',
                'description' => 'Complete 100 tasks',
                'icon' => '🎯',
                'type' => 'milestone',
                'requirement' => 100,
            ],
            [
                'name' => 'Dedicated Worker',
                'description' => 'Complete 500 images',
                'icon' => '🏆',
                'type' => 'milestone',
                'requirement' => 500,
            ],
            [
                'name' => 'Speed Demon',
                'description' => 'Answer 50 questions in under 2 seconds',
                'icon' => '⚡',
                'type' => 'speed',
                'requirement' => 50,
            ],
            [
                'name' => 'Sharpshooter',
                'description' => 'Achieve 95% accuracy over 100 responses',
                'icon' => '🎖️',
                'type' => 'accuracy',
                'requirement' => 95,
            ],
            [
                'name' => 'Top Performer',
                'description' => 'Reach top 10 in weekly rankings',
                'icon' => '🥇',
                'type' => 'weekly_rank',
                'requirement' => 10,
            ],
            [
                'name' => 'Recruiter',
                'description' => 'Successfully refer 5 users',
                'icon' => '👥',
                'type' => 'referral',
                'requirement' => 5,
            ],
        ];
    }
}
