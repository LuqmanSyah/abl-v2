<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->date('attendance_date')->nullable()->after('employee_id');
            $table->text('review_reason')->nullable()->after('status');
        });

        foreach (DB::table('attendances')->select('id', 'captured_at')->get() as $attendance) {
            DB::table('attendances')->where('id', $attendance->id)->update([
                'attendance_date' => substr($attendance->captured_at, 0, 10),
            ]);
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('duty_trip_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['duty_trip_id', 'employee_id']);
            $table->unique(['duty_trip_id', 'employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['duty_trip_id', 'employee_id', 'attendance_date']);
            $table->unique(['duty_trip_id', 'employee_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['duty_trip_id']);
            $table->dropColumn(['attendance_date', 'review_reason']);
        });
    }
};
