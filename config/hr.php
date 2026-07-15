<?php

return [
    'attendance_clock_tolerance_minutes' => (int) env('ATTENDANCE_CLOCK_TOLERANCE_MINUTES', 15),
    'photo_retention_days' => (int) env('PHOTO_RETENTION_DAYS', 365),
    'backup_keep' => (int) env('BACKUP_KEEP', 14),
];
