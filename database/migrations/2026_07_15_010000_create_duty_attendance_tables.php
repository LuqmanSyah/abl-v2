<?php

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('location_name');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default(DutyTripStatus::Active->value)->index();
            $table->timestamps();
            $table->index(['employee_id', 'starts_at']);
            $table->index(['manager_id', 'status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('received_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('accuracy_meters');
            $table->unsignedInteger('distance_meters');
            $table->string('photo_path');
            $table->string('status')->default(AttendanceStatus::Valid->value)->index();
            $table->text('review_reason')->nullable();
            $table->timestamps();
            $table->unique(['duty_trip_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('duty_trips');
    }
};
