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
            $table->date('attendance_date')->nullable()->after('attendance_request_id');
            $table->string('session_key')->nullable()->after('attendance_date');
        });

        DB::table('attendances')
            ->select(['id', 'attendance_request_id', 'recorded_at'])
            ->orderBy('id')
            ->each(function (object $attendance): void {
                DB::table('attendances')
                    ->where('id', $attendance->id)
                    ->update([
                        'attendance_date' => date('Y-m-d', strtotime($attendance->recorded_at)),
                        'session_key' => $attendance->attendance_request_id
                            ? 'request:'.$attendance->attendance_request_id
                            : 'office',
                    ]);
            });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'attendance_date', 'session_key', 'type'],
                'attendances_session_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_session_unique');
            $table->dropColumn(['attendance_date', 'session_key']);
        });
    }
};
