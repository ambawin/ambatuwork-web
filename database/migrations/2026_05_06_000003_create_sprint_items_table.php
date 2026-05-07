<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sprint_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('backlog_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('committed_points')->nullable();
            $table->foreignId('added_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['sprint_id', 'backlog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_items');
    }
};