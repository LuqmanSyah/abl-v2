<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Akun Anda sudah dinonaktifkan. Hubungi Admin SDM bila ini tidak sesuai.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 403)
            : redirect()->route('login')->withErrors(['email' => $message]);
    }
}
