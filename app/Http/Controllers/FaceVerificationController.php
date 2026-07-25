<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class FaceVerificationController extends Controller
{
    public function extract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('photo')->store('face-temp', 'local');

        try {
            $descriptor = $this->extractViaPython($path);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            report($exception);

            return response()->json([
                'descriptor' => null,
                'error' => 'Server face verification unavailable: ' . $exception->getMessage(),
            ], 503);
        }

        Storage::disk('local')->delete($path);

        if ($descriptor === null) {
            return response()->json([
                'descriptor' => null,
                'error' => 'No face detected in photo.',
            ]);
        }

        return response()->json(['descriptor' => $descriptor]);
    }

    private function extractViaPython(string $imagePath): ?array
    {
        $scriptPath = base_path('resources/python/face_extract.py');
        $absolutePath = Storage::disk('local')->path($imagePath);

        $process = new Process([
            PHP_BINARY === '' ? 'python3' : 'python3',
            $scriptPath,
            $absolutePath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new BusinessRuleException('Face extraction process failed: ' . $process->getErrorOutput());
        }

        $result = json_decode($process->getOutput(), true);

        if (!is_array($result) || !array_key_exists('descriptor', $result)) {
            throw new BusinessRuleException('Invalid response from face extraction.');
        }

        return $result['descriptor'];
    }
}
