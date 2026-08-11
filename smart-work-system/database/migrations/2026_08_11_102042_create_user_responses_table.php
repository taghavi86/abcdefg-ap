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
        Schema::create('user_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('image_id')->constrained()->onDelete('cascade');
            $table->text('user_answer')->nullable(); // User's answer text or selected option
            $table->boolean('is_correct')->default(false); // For level_test
            $table->integer('response_time_seconds')->default(0); // Time taken to answer
            $table->integer('accuracy_score_earned')->default(0); // 0 or 5 coins
            $table->integer('speed_score_earned')->default(0); // 0, 1, 3, or 5 coins based on speed
            $table->integer('total_coins_earned')->default(0); // Total for this response
            $table->enum('type', ['workbench', 'level_test'])->default('workbench');
            $table->boolean('is_level_test')->default(false); // Flag for level test responses
            $table->integer('test_attempt_number')->default(0); // Which attempt (1-4 for level test)
            
            // For workbench approval workflow
            $table->boolean('is_approved')->default(false); // Pending/approved/rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_review'])->default('pending');
            
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index('is_approved');
            $table->index('is_level_test');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_responses');
    }
};
