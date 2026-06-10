<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'priority_rank']);
            $table->dropColumn('priority_rank');
        });
    }

    public function down(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->decimal('priority_rank', 20, 10)->nullable()->after('status');
            $table->index(['project_id', 'priority_rank']);
        });
    }
};
