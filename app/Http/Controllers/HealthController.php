<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Liveness probe.
 *
 * Deliberately does no I/O: it answers "is this process up and serving", which
 * is the question a load balancer asks. Readiness -- can it reach the database
 * and the queue -- is a separate concern and a separate endpoint.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
