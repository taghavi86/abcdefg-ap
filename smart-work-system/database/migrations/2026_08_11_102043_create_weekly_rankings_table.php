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
        Schema::create('weekly_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('week_number'); // ISO week number (1-53)
            $table->integer('year');
            $table->integer('total_tasks_completed')->default(0);
            $table->integer('total_coins_earned')->default(0);
            $table->integer('accuracy_rate')->default(0); // Percentage (0-100)
            $table->integer('speed_score_total')->default(0); // Total speed bonus
            $table->integer('rank')->nullable(); // Final rank (1, 2, 3...)
            $table->boolean('is_in_top_10')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->decimal('cash_reward', 10, 2)->default(200000); // 200,000 Tomans for top 10
            $table->timestamp('paid_at')->nullable();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->timestamps();
            
            $table->unique(['user_id', 'week_number', 'year']);
            $table->index(['week_number', 'year']);
            $table->index('rank');
            $table->index('is_in_top_10');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_rankings');
    }
};
