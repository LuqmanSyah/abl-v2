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

        DB::table('attendances')
            ->select(['user_id', 'attendance_date', 'session_key', 'type'])
            ->selectRaw('MIN(id) AS keep_id')
            ->groupBy('user_id', 'attendance_date', 'session_key', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('keep_id')
            ->get()
            ->each(function (object $duplicate): void {
                $duplicateIds = DB::table('attendances')
                    ->where('user_id', $duplicate->user_id)
                    ->where('attendance_date', $duplicate->attendance_date)
                    ->where('session_key', $duplicate->session_key)
                    ->where('type', $duplicate->type)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->pluck('id');

                DB::table('daily_attendance_summaries')
                    ->whereIn('check_in_id', $duplicateIds)
                    ->update(['check_in_id' => $duplicate->keep_id]);
                DB::table('daily_attendance_summaries')
                    ->whereIn('check_out_id', $duplicateIds)
                    ->update(['check_out_id' => $duplicate->keep_id]);
                DB::table('attendances')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
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
