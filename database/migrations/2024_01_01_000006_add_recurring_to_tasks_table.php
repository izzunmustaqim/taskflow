<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('recurrence_type')->nullable()->after('sort_order');
            $table->integer('recurrence_interval')->default(1)->after('recurrence_type');
            $table->timestamp('next_occurrence_at')->nullable()->after('recurrence_interval');
            $table->foreignId('parent_task_id')->nullable()->after('next_occurrence_at')->constrained('tasks')->nullOnDelete();

            // Index for finding tasks that need to recur
            $table->index('recurrence_type');
            $table->index('next_occurrence_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['parent_task_id']);
            $table->dropColumn([
                'recurrence_type',
                'recurrence_interval',
                'next_occurrence_at',
                'parent_task_id',
            ]);
        });
    }
};
