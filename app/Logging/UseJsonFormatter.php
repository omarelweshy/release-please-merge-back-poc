<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

/**
 * Laravel log tap: renders a channel as one JSON object per line.
 *
 * Line-per-object is what log shippers expect, and it survives multi-line
 * payloads -- a stack trace in a text log breaks every parser that assumes one
 * event per line.
 *
 * Wire it up with `'tap' => [UseJsonFormatter::class]` on a channel; see
 * config/logging.php.
 */
class UseJsonFormatter
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new AddRequestContext);

        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(
                new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, appendNewline: true)
            );
        }
    }
}
