<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('weight', 5, 2);
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('period');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('manager_kpi_score', 5, 2)->default(0);
            $table->decimal('final_merit_score', 5, 2)->default(0);
            $table->string('grade')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('review_kpi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_review_id')->constrained();
            $table->foreignId('kpi_id')->constrained();
            $table->decimal('self_score', 5, 2)->nullable();
            $table->text('self_notes')->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->text('manager_notes')->nullable();
            $table->decimal('weight', 5, 2);
            $table->decimal('subtotal_score', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_kpi_details');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('kpis');
    }
};
