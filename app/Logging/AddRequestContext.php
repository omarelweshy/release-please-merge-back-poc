<?php

namespace App\Logging;

use App\Http\Middleware\AttachRequestId;
use App\Support\BuildInfo;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Stamps every log record with the request it came from and the build it ran on.
 *
 * Without this a log line is an orphan: it says what happened but not which
 * request caused it or which deployment produced it. With it, the request id a
 * caller already has in their X-Request-Id response header is enough to pull
 * every line for that request, and the version tells you whether you are
 * looking at the release you think you are.
 *
 * A console run has no such header, so request_id comes out null rather than
 * invented. That is decided by the header being absent, not by asking whether
 * we are running in console -- under PHPUnit that answer is always "yes", which
 * would make this untestable.
 */
class AddRequestContext implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: $record->extra + [
            'request_id' => $this->requestId(),
            'version' => BuildInfo::version(),
            'environment' => config('app.env'),
        ]);
    }

    private function requestId(): ?string
    {
        return request()?->headers->get(AttachRequestId::HEADER);
    }
}
