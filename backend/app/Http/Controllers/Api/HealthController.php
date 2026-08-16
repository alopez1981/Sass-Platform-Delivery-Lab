<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Three distinct endpoints on purpose — see docs/observability.md:
 *
 * - Liveness: is the PHP process itself alive? No dependency is checked.
 *   An orchestrator uses this to decide whether to *restart* the container.
 *   Must stay trivially cheap — never touch the database or queue here.
 * - Readiness: can this instance actually serve traffic right now? Checks
 *   the dependencies a request would need (DB, cache). An orchestrator uses
 *   this to decide whether to *route* traffic here — a container can be
 *   alive but not ready (e.g. database briefly unreachable).
 * - Application health: a superset of readiness meant for humans/dashboards,
 *   not orchestrators — includes the queue and basic version info. Slower
 *   and chattier on purpose; never wire an orchestrator probe to this one.
 */
class HealthController extends Controller
{
    public function live()
    {
        return response()->json(['status' => 'ok']);
    }

    public function ready()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $allOk = collect($checks)->every(fn (string $result) => $result === 'ok');

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }

    public function app()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $allOk = collect($checks)->every(fn (string $result) => $result === 'ok');

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ], $allOk ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function checkCache(): string
    {
        try {
            $key = 'health-check-'.Str::random(8);
            Cache::put($key, true, 5);
            $ok = Cache::pull($key) === true;

            return $ok ? 'ok' : 'error: round-trip mismatch';
        } catch (Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function checkQueue(): string
    {
        try {
            // Opens a real connection to RabbitMQ and asks the queue's
            // depth — a genuine functional check, not just "is the driver
            // configured".
            Queue::connection('rabbitmq')->size('default');

            return 'ok';
        } catch (Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }
}
