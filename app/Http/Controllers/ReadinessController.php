<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Readiness probe: can this instance actually serve traffic?
 *
 * The counterpart to /health, which only answers "is the process alive". This
 * one touches the dependencies, so an instance that is up but cannot reach its
 * database is taken out of rotation instead of quietly failing requests.
 *
 * Returns 503 when any dependency is down, because an orchestrator reads the
 * status code, not the body.
 */
class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo()),
        ];

        $ready = ! in_array(false, $checks, true);

        return response()->json(
            ['ready' => $ready, 'checks' => $checks],
            $ready ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    private function check(callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
