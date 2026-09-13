<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReadinessEndpointTest extends TestCase
{
    public function test_it_reports_ready_when_the_database_answers(): void
    {
        $this->getJson('/api/ready')
            ->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonPath('checks.database', true);
    }

    public function test_it_returns_503_when_the_database_is_unreachable(): void
    {
        DB::shouldReceive('connection')->andThrow(new RuntimeException('down'));

        $this->getJson('/api/ready')
            ->assertServiceUnavailable()
            ->assertJsonPath('ready', false)
            ->assertJsonPath('checks.database', false);
    }
}
