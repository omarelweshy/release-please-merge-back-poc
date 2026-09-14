<?php

namespace Tests\Unit;

use App\Http\Middleware\AttachRequestId;
use App\Logging\AddRequestContext;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class AddRequestContextTest extends TestCase
{
    private function record(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'json',
            level: Level::Info,
            message: 'something happened',
        );
    }

    public function test_it_stamps_the_build_version(): void
    {
        config(['version.version' => '9.9.9']);

        $record = (new AddRequestContext)($this->record());

        $this->assertSame('9.9.9', $record->extra['version']);
    }

    public function test_it_carries_the_request_id_through(): void
    {
        request()->headers->set(AttachRequestId::HEADER, 'abc-123');

        $record = (new AddRequestContext)($this->record());

        $this->assertSame('abc-123', $record->extra['request_id']);
    }

    public function test_a_request_without_the_header_has_no_request_id(): void
    {
        $record = (new AddRequestContext)($this->record());

        $this->assertNull($record->extra['request_id']);
    }

    public function test_it_preserves_extra_that_is_already_there(): void
    {
        $record = (new AddRequestContext)($this->record()->with(extra: ['keep' => 'me']));

        $this->assertSame('me', $record->extra['keep']);
    }
}
