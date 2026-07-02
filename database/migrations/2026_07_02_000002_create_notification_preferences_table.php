<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('email_due_soon')->default(true);
            $table->boolean('email_overdue')->default(true);
            $table->boolean('email_reminders')->default(true);
            $table->boolean('in_app_due_soon')->default(true);
            $table->boolean('in_app_overdue')->default(true);
            $table->boolean('in_app_reminders')->default(true);
            $table->integer('reminder_hours_before')->default(24); // Hours before due date
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
