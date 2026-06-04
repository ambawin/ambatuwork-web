<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retro_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retrospective_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // went_well, problem, idea, action
            $table->text('body');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('due_sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retro_items');
    }
};
