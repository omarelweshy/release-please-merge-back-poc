<?php

namespace App\Http\Controllers;

use App\Support\BuildInfo;
use Illuminate\Http\JsonResponse;

/**
 * Reports what this instance is running.
 *
 * The version comes from config/version.php, which release-please rewrites in
 * the release PR. The commit comes from the build, and is what tells two
 * deployments of the same version apart.
 */
class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(BuildInfo::toArray() + [
            'environment' => config('app.env'),
        ]);
    }
}
