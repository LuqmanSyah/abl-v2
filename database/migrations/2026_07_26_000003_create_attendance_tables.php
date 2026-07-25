<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->unique();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('status');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->string('flow_type');
            $table->string('destination_name');
            $table->text('destination_address');
            $table->decimal('target_latitude', 10, 7);
            $table->decimal('target_longitude', 10, 7);
            $table->unsignedInteger('allowed_radius_meters');
            $table->dateTime('duty_start_datetime');
            $table->dateTime('duty_end_datetime');
            $table->text('reason');
            $table->string('status');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('attendance_request_id')->nullable()->constrained();
            $table->string('type');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('distance_to_target_meters', 10, 2);
            $table->boolean('is_fallback')->default(false);
            $table->text('address_snapshot');
            $table->string('photo_path');
            $table->boolean('is_radius_exception')->default(false);
            $table->text('exception_reason')->nullable();
            $table->string('status');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('daily_attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('attendance_request_id')->nullable()->constrained();
            $table->date('date');
            $table->foreignId('check_in_id')->nullable()->constrained('attendances');
            $table->foreignId('check_out_id')->nullable()->constrained('attendances');
            $table->string('status');
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_attendance_summaries');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('holidays');
    }
};
