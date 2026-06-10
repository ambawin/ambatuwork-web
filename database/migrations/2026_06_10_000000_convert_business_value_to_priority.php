<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->string('priority')->default('medium')->after('priority_rank');
        });

        // Map existing numeric values to priority strings
        DB::table('backlog_items')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $val = $item->business_value;
                if ($val === null) {
                    $priority = 'medium';
                } elseif ($val >= 81) {
                    $priority = 'highest';
                } elseif ($val >= 61) {
                    $priority = 'high';
                } elseif ($val >= 41) {
                    $priority = 'medium';
                } elseif ($val >= 21) {
                    $priority = 'low';
                } else {
                    $priority = 'lowest';
                }

                DB::table('backlog_items')
                    ->where('id', $item->id)
                    ->update(['priority' => $priority]);
            }
        });

        Schema::table('backlog_items', function (Blueprint $table) {
            $table->dropColumn('business_value');
        });
    }

    public function down(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('business_value')->nullable()->after('priority_rank');
        });

        // Reverse mapping
        DB::table('backlog_items')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $priority = $item->priority;
                if ($priority === 'highest') {
                    $val = 90;
                } elseif ($priority === 'high') {
                    $val = 70;
                } elseif ($priority === 'medium') {
                    $val = 50;
                } elseif ($priority === 'low') {
                    $val = 30;
                } else {
                    $val = 10;
                }

                DB::table('backlog_items')
                    ->where('id', $item->id)
                    ->update(['business_value' => $val]);
            }
        });

        Schema::table('backlog_items', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
