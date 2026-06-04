<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sprint_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('backlog_item_id')->constrained()->cascadeOnDelete();
            $table->string('decision'); // accepted, rejected, carry_over
            $table->text('notes')->nullable();
            $table->foreignId('decided_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sprint_review_id', 'backlog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_review_items');
    }
};
