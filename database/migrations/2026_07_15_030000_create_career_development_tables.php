<?php

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('position_competency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('required_level');
            $table->timestamps();
            $table->unique(['position_id', 'competency_id']);
        });

        Schema::create('employee_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->date('assessed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'competency_id']);
        });

        Schema::create('career_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('target_position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(TrainingRequestStatus::PendingManager->value)->index();
            $table->text('reason')->nullable();
            $table->text('manager_notes')->nullable();
            $table->text('hr_result')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('manager_decided_at')->nullable();
            $table->foreignId('hr_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_id']);
        });

        Schema::create('mentorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(MentoringStatus::Pending->value)->index();
            $table->string('topic');
            $table->text('target');
            $table->dateTime('requested_at');
            $table->dateTime('scheduled_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->text('result')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('mentorings');
        Schema::dropIfExists('training_requests');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('career_goals');
        Schema::dropIfExists('employee_competencies');
        Schema::dropIfExists('position_competency');
        Schema::dropIfExists('competencies');
    }
};
