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
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('employee_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->decimal('target', 15, 2);
            $table->decimal('achievement', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['review_period_id', 'employee_id', 'name']);
            $table->index(['manager_id', 'review_period_id']);
        });

        Schema::create('merit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('kpi_score', 6, 2);
            $table->decimal('attendance_score', 6, 2);
            $table->decimal('total_score', 6, 2);
            $table->timestamps();
            $table->unique(['review_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merit_results');
        Schema::dropIfExists('employee_kpis');
        Schema::dropIfExists('review_periods');
    }
};
