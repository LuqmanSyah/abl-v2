<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class HandleForbiddenPanelPage
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if (! $this->shouldReplaceResponse($request) || ! $this->isForbidden($exception)) {
                throw $exception;
            }

            return $this->redirectWithNotification($request);
        }

        if ($this->shouldReplaceResponse($request) && $response->getStatusCode() === 403) {
            return $this->redirectWithNotification($request);
        }

        return $response;
    }

    private function isForbidden(Throwable $exception): bool
    {
        return $exception instanceof AuthorizationException
            || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403);
    }

    private function shouldReplaceResponse(Request $request): bool
    {
        return $request->isMethod('GET') && ! $request->expectsJson();
    }

    private function redirectWithNotification(Request $request): Response
    {
        Notification::make()
            ->title('Halaman tidak dapat diakses')
            ->body('Anda tidak memiliki izin atau data sudah dikunci.')
            ->warning()
            ->send();

        return new RedirectResponse($this->redirectUrl($request));
    }

    private function redirectUrl(Request $request): string
    {
        $previous = $request->headers->get('referer');

        if ($previous && $this->isSafePreviousUrl($request, $previous)) {
            return $previous;
        }

        $user = $request->user();

        if ($user) {
            foreach (Filament::getPanels() as $panel) {
                if ($user->canAccessPanel($panel)) {
                    return $panel->getUrl();
                }
            }
        }

        return Filament::getUrl() ?? url('/');
    }

    private function isSafePreviousUrl(Request $request, string $previous): bool
    {
        $previousParts = parse_url($previous);
        $currentParts = parse_url($request->fullUrl());

        if (! $previousParts || ! $currentParts) {
            return false;
        }

        return ($previousParts['scheme'] ?? null) === ($currentParts['scheme'] ?? null)
            && ($previousParts['host'] ?? null) === ($currentParts['host'] ?? null)
            && ($previousParts['port'] ?? null) === ($currentParts['port'] ?? null)
            && rtrim($previous, '/') !== rtrim($request->fullUrl(), '/');
    }
}
