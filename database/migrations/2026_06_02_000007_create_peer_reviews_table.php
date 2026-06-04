<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peer_review_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('collaboration_score'); // 1-5
            $table->unsignedTinyInteger('delivery_score'); // 1-5
            $table->unsignedTinyInteger('communication_score'); // 1-5
            $table->text('continue_feedback')->nullable();
            $table->text('improve_feedback')->nullable();
            $table->boolean('is_anonymous_to_reviewee')->default(true);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['peer_review_cycle_id', 'reviewer_user_id', 'reviewee_user_id'], 'peer_reviews_cycle_reviewer_reviewee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_reviews');
    }
};
