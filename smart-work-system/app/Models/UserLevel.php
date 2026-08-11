<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_score',
        'max_score',
        'income_multiplier',
        'description',
        'is_active',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'income_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Level has many users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'level_id');
    }

    /**
     * Scope: Active levels only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get level by score
     */
    public static function getLevelByScore($score)
    {
        return self::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    /**
     * Seed default levels
     */
    public static function seedDefaultLevels()
    {
        $levels = [
            [
                'name' => 'پایه',
                'slug' => 'basic',
                'min_score' => 60,
                'max_score' => 69,
                'income_multiplier' => 1.0,
                'description' => 'سطح پایه - ضریب درآمدی ۱',
                'is_active' => true,
            ],
            [
                'name' => 'پیشرو',
                'slug' => 'advanced',
                'min_score' => 70,
                'max_score' => 89,
                'income_multiplier' => 2.0,
                'description' => 'سطح پیشرو - ضریب درآمدی ۲',
                'is_active' => true,
            ],
            [
                'name' => 'ممتاز',
                'slug' => 'excellent',
                'min_score' => 90,
                'max_score' => 120,
                'income_multiplier' => 3.5,
                'description' => 'سطح ممتاز - ضریب درآمدی ۳.۵',
                'is_active' => true,
            ],
        ];

        foreach ($levels as $levelData) {
            self::updateOrCreate(
                ['slug' => $levelData['slug']],
                $levelData
            );
        }
    }
}
