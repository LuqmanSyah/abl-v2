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
        Schema::create('duty_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('duty_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('duty_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination');
            $table->text('purpose');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location_name');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters');
            $table->string('supporting_document_path')->nullable();
            $table->string('status')->default(DutyTripStatus::Pending->value)->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'starts_at']);
            $table->index(['manager_id', 'status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('captured_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('accuracy_meters')->nullable();
            $table->unsignedInteger('distance_meters');
            $table->string('photo_path');
            $table->string('status')->default(AttendanceStatus::Valid->value)->index();
            $table->boolean('mock_location_suspected')->default(false);
            $table->timestamps();
            $table->unique(['duty_trip_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('duty_trips');
        Schema::dropIfExists('duty_locations');
    }
};
