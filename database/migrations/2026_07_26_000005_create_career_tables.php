<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_position_id')->constrained('positions');
            $table->foreignId('next_position_id')->constrained('positions');
            $table->unsignedSmallInteger('min_experience_months');
            $table->string('min_merit_grade');
            $table->timestamps();
        });

        Schema::create('individual_development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('mentor_id')->constrained('users');
            $table->string('title');
            $table->text('action_plan');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->date('target_completion_date');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('from_position_id')->constrained('positions');
            $table->foreignId('to_position_id')->constrained('positions');
            $table->foreignId('proposed_by')->constrained('users');
            $table->decimal('readiness_score', 5, 2);
            $table->string('status')->default('proposed');
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('individual_development_plans');
        Schema::dropIfExists('career_paths');
    }
};
