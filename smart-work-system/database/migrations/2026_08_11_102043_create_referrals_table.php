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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // User who invited
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade'); // User who was invited
            $table->string('referral_code');
            $table->enum('status', ['pending', 'completed', 'paid', 'rejected'])->default('pending');
            $table->decimal('reward_amount', 10, 2)->default(100000); // 100,000 Tomans reward
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_fake_detected')->default(false); // For fraud detection
            $table->text('fraud_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['referrer_id', 'referred_id']);
            $table->index('referral_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
