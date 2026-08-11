<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First seed user levels
        $this->call(UserLevelSeeder::class);

        // Get base level
        $baseLevel = \App\Models\UserLevel::where('slug', 'basic')->first();

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'phone' => '09123456789',
            'national_code' => '1234567890',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'referral_code' => Str::random(10),
            'level_id' => $baseLevel?->id,
            'status' => true,
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'phone' => '09123456788',
            'national_code' => '1234567891',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'referral_code' => Str::random(10),
            'level_id' => $baseLevel?->id,
            'status' => true,
        ]);
    }
}
