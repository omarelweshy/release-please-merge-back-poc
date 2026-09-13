<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionEndpointTest extends TestCase
{
    public function test_it_reports_the_configured_version(): void
    {
        config(['version.version' => '1.2.3']);

        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonPath('version', '1.2.3');
    }

    public function test_it_reports_the_current_environment(): void
    {
        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonStructure(['version', 'environment']);
    }
}
