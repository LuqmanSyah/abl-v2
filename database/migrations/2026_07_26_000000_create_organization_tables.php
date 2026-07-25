<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained();
            $table->string('title');
            $table->unsignedTinyInteger('level');
            $table->timestamps();
        });

        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->unsignedSmallInteger('late_tolerance_minutes');
            $table->unsignedSmallInteger('alfa_cutoff_minutes');
            $table->timestamps();
        });

        Schema::create('branch_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('allowed_radius_meters');
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->timestamps();
        });

        Schema::create('position_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained();
            $table->foreignId('skill_id')->constrained();
            $table->unsignedTinyInteger('min_required_level');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('branch_offices');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
