<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request a stable id and echoes it back on the response.
 *
 * An inbound X-Request-Id is trusted and reused so a caller can correlate its
 * own logs with ours; otherwise one is minted. Either way the id is on the
 * request for the duration, so anything logging downstream can pick it up.
 */
class AttachRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header(self::HEADER) ?: (string) Str::uuid();

        $request->headers->set(self::HEADER, $id);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }
}
