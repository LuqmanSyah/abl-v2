<?php

return [
    'photo_retention_days' => (int) env('PHOTO_RETENTION_DAYS', 365),
    'backup_keep' => (int) env('BACKUP_KEEP', 14),
];
