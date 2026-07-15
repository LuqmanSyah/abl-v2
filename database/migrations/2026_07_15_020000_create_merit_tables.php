<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedTinyInteger('kpi_weight')->default(40);
            $table->unsignedTinyInteger('discipline_weight')->default(20);
            $table->unsignedTinyInteger('manager_weight')->default(20);
            $table->unsignedTinyInteger('review_360_weight')->default(20);
            $table->decimal('base_bonus', 15, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedTinyInteger('weight');
            $table->timestamps();
            $table->unique(['review_period_id', 'name']);
        });

        Schema::create('employee_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->decimal('target', 15, 2);
            $table->decimal('achievement', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['kpi_indicator_id', 'employee_id']);
            $table->index(['manager_id', 'review_period_id']);
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedTinyInteger('score');
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['review_period_id', 'reviewer_id', 'reviewee_id', 'type'], 'performance_review_unique');
        });

        Schema::create('merit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('kpi_score', 6, 2);
            $table->decimal('discipline_score', 6, 2);
            $table->decimal('manager_score', 6, 2);
            $table->decimal('review_360_score', 6, 2);
            $table->decimal('total_score', 6, 2);
            $table->decimal('estimated_bonus', 15, 2);
            $table->foreignId('manager_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_verified_at')->nullable();
            $table->foreignId('hr_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_verified_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['review_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merit_results');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('employee_kpis');
        Schema::dropIfExists('kpi_indicators');
        Schema::dropIfExists('review_periods');
    }
};
