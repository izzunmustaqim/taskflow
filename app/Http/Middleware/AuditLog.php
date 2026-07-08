<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AuditLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log security-relevant events
        if ($this->shouldLog($request, $response)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     */
    private function shouldLog(Request $request, Response $response): bool
    {
        // Log authentication attempts
        if ($request->is('login') || $request->is('register')) {
            return true;
        }

        // Log password changes
        if ($request->is('password') || $request->is('password/*')) {
            return true;
        }

        // Log profile updates
        if ($request->is('profile') && $request->isMethod('PUT')) {
            return true;
        }

        // Log account deletion
        if ($request->is('profile') && $request->isMethod('DELETE')) {
            return true;
        }

        // Log backup/restore operations
        if ($request->is('tasks/backup') || $request->is('tasks/restore')) {
            return true;
        }

        // Log failed attempts (4xx responses)
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            return true;
        }

        return false;
    }

    /**
     * Log the request details.
     */
    private function logRequest(Request $request, Response $response): void
    {
        $user = $request->user();
        $userId = $user?->id ?? 'anonymous';

        $logData = [
            'user_id' => $userId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add request data for non-GET requests (except sensitive fields)
        if (! $request->isMethod('GET')) {
            $sanitizedData = $request->except(['password', 'password_confirmation', 'current_password']);
            $logData['request_data'] = $sanitizedData;
        }

        // Log based on status code
        if ($response->getStatusCode() >= 400) {
            Log::warning('Security event - failed request', $logData);
        } else {
            Log::info('Security event - successful action', $logData);
        }
    }
}
