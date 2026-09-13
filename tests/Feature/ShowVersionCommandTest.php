<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShowVersionCommandTest extends TestCase
{
    public function test_it_prints_the_configured_version(): void
    {
        config(['version.version' => '1.2.3']);

        $this->artisan('app:version')
            ->expectsOutputToContain('1.2.3')
            ->assertSuccessful();
    }

    public function test_short_prints_only_the_version(): void
    {
        config(['version.version' => '1.2.3']);

        $this->artisan('app:version', ['--short' => true])
            ->expectsOutput('1.2.3')
            ->assertSuccessful();
    }
}
