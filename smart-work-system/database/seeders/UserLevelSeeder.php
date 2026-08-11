<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserLevel::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'پایه',
                'min_score' => 60,
                'max_score' => 69,
                'income_multiplier' => 1.0,
                'description' => 'سطح پایه برای کاربران جدید',
                'is_active' => true,
            ]
        );

        UserLevel::updateOrCreate(
            ['slug' => 'advanced'],
            [
                'name' => 'پیشرو',
                'min_score' => 70,
                'max_score' => 89,
                'income_multiplier' => 2.0,
                'description' => 'سطح پیشرو برای کاربران با تجربه',
                'is_active' => true,
            ]
        );

        UserLevel::updateOrCreate(
            ['slug' => 'excellent'],
            [
                'name' => 'ممتاز',
                'min_score' => 90,
                'max_score' => 120,
                'income_multiplier' => 3.5,
                'description' => 'سطح ممتاز برای کاربران حرفه‌ای',
                'is_active' => true,
            ]
        );
    }
}
