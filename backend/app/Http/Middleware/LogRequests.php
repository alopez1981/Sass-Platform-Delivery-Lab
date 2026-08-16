<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns a correlation ID to every request (reusing one from an upstream
 * proxy/load balancer if present in X-Correlation-Id, otherwise generating
 * one) and logs one structured line per request.
 *
 * The ID is added to Laravel's Context, which Laravel automatically:
 *  - merges into every log line written during this request (see
 *    config/logging.php's JsonFormatter — no need to pass it manually to
 *    each Log::info() call), and
 *  - propagates into any job dispatched during this request (e.g.
 *    NotifyRequestCreated), so a single correlation ID can trace a request
 *    all the way through its async side effects too.
 */
class LogRequests
{
    private const HEADER = 'X-Correlation-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header(self::HEADER) ?: (string) Str::uuid();

        Context::add('correlation_id', $correlationId);

        $startedAt = microtime(true);

        $response = $next($request);

        Context::add('user_id', $request->user()?->id);

        Log::info('http.request', [
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }
}
