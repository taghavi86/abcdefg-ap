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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_path'); // Local path for admin
            $table->string('image_url')->nullable(); // Full URL for download host
            $table->string('download_host_url')->nullable(); // URL on download host
            $table->enum('type', ['workbench', 'level_test'])->default('workbench');
            $table->integer('question_count')->default(3); // Number of questions per image
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bulk_import')->default(false); // For bulk import tracking
            $table->string('bulk_import_batch_id')->nullable(); // For grouping bulk imports
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('is_active');
            $table->index('bulk_import_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
