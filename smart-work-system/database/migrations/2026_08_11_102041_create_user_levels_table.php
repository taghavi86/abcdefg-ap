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
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // پایه, پیشرو, ممتاز
            $table->string('slug')->unique(); // basic, advanced, excellent
            $table->integer('min_score'); // 60, 70, 90
            $table->integer('max_score'); // 69, 89, 120
            $table->decimal('income_multiplier', 3, 1)->default(1.0); // 1, 2, 3.5
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('min_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
