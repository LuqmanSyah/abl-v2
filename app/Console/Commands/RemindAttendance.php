<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use App\Notifications\AttendanceReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RemindAttendance extends Command
{
    protected $signature = 'attendance:remind';
    protected $description = 'Kirim pengingat absensi ke pegawai yang memiliki dinas hari ini tapi belum absen';

    public function handle(): int
    {
        $today = CarbonImmutable::today();
        $trips = DutyTrip::whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->where('status', \App\Enums\DutyTripStatus::Approved)
            ->with('employee')
            ->get();

        $sent = 0;

        foreach ($trips as $trip) {
            $alreadyAttended = Attendance::where('duty_trip_id', $trip->id)
                ->where('employee_id', $trip->employee_id)
                ->whereDate('captured_at', $today)
                ->exists();

            if ($alreadyAttended) {
                continue;
            }

            $trip->employee->notify(new AttendanceReminder($trip));
            $sent++;
        }

        $this->info("{$sent} pengingat absensi hari ini terkirim.");

        return 0;
    }
}
