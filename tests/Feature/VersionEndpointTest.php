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

    public function test_it_reports_the_build_commit_when_one_was_baked_in(): void
    {
        putenv('APP_COMMIT=abcdef1234567890');

        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonPath('commit', 'abcdef12');

        putenv('APP_COMMIT');
    }

    public function test_it_reports_a_null_commit_rather_than_guessing(): void
    {
        putenv('APP_COMMIT');

        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonPath('commit', null);
    }

    public function test_it_reports_the_current_environment(): void
    {
        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonStructure(['version', 'commit', 'environment']);
    }
}
