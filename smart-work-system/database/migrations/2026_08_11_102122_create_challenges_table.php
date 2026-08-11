<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "چالش ظهرگاهی"
            $table->text('description')->nullable();
            $table->enum('type', ['speed_challenge', 'bonus_task', 'double_coins'])->default('double_coins');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('bonus_multiplier', 3, 1)->default(2.0); // 2x coins during challenge
            $table->integer('max_bonus_per_user')->default(100); // Max bonus coins per user
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false); // Daily/weekly recurring challenge
            $table->string('recurring_pattern')->nullable(); // e.g., "daily 12:00-14:00"
            $table->integer('participating_users_count')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
