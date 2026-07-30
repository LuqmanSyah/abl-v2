<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('target');
            $table->text('current_gap');
            $table->text('recommended_action');
            $table->date('review_date')->nullable();
            $table->timestamps();
        });

        Schema::create('development_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('reason');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('manager_notes')->nullable();
            $table->timestamps();
            $table->index(['manager_id', 'status']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('subject');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('development_requests');
        Schema::dropIfExists('development_plans');
    }
};
