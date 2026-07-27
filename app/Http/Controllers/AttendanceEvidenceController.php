<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceEvidenceController
{
    public function __invoke(Request $request, Attendance $attendance): StreamedResponse
    {
        abort_unless($attendance->canViewEvidence($request->user()), 403);

        $path = $attendance->photo_path;
        abort_unless(
            is_string($path)
                && str_starts_with($path, 'attendance/')
                && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path),
            404,
        );

        return Storage::disk('local')->response($path);
    }
}
