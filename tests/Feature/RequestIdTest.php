<?php

namespace Tests\Feature;

use App\Http\Middleware\AttachRequestId;
use Tests\TestCase;

class RequestIdTest extends TestCase
{
    public function test_it_mints_a_request_id_when_none_is_supplied(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertHeader(AttachRequestId::HEADER);
    }

    public function test_it_reuses_an_inbound_request_id(): void
    {
        $this->withHeader(AttachRequestId::HEADER, 'abc-123')
            ->getJson('/api/health')
            ->assertOk()
            ->assertHeader(AttachRequestId::HEADER, 'abc-123');
    }
}
