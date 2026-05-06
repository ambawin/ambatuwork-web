<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backlog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('story');
            $table->string('status')->default('backlog');
            $table->decimal('priority_rank', 20, 10)->nullable();
            $table->unsignedTinyInteger('business_value')->nullable();
            $table->unsignedSmallInteger('estimate_points')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlog_items');
    }
};