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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->enum('type', ['workbench', 'level_test'])->default('workbench');
            // For workbench: no options needed (numeric answer only)
            // For level_test: store correct answer for automatic scoring
            $table->string('correct_answer')->nullable(); // Numeric answer for level_test
            $table->integer('accuracy_score')->default(5); // Max 5 coins for accuracy (0 or 5)
            $table->integer('speed_score')->default(0); // 0-5 coins based on speed
            $table->integer('total_score')->default(10); // Max 10 coins per question
            $table->integer('order_index')->default(0); // Question order (1, 2, 3)
            $table->boolean('is_control_question')->default(false); // For quality control
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('image_id');
            $table->index('type');
            $table->index('is_control_question');
            $table->index('order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
