<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Reports the deployed application version.
 *
 * The value comes from config/version.php, which release-please rewrites in the
 * release PR. That makes this endpoint the cheapest way to answer "what is
 * actually running in this environment" -- which is the question the old
 * branching flow could not answer from any branch.
 */
class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'version' => config('version.version'),
            'environment' => config('app.env'),
        ]);
    }
}
