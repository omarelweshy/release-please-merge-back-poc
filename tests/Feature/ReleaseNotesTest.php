<?php

namespace Tests\Feature;

use App\Support\ReleaseNotes;
use Tests\TestCase;

class ReleaseNotesTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'changelog').'.md';

        file_put_contents($this->path, <<<'MD'
            # Changelog

            ## [0.3.0](https://example.test/compare/v0.2.0...v0.3.0) (2026-09-13)

            ### Features

            * report the build commit alongside the version

            ## [0.2.0](https://example.test/compare/v0.1.0...v0.2.0) (2026-09-12)

            ### Bug Fixes

            * don't open sync PRs that change nothing
            MD);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_it_lists_documented_versions_newest_first(): void
    {
        $this->assertSame(['0.3.0', '0.2.0'], (new ReleaseNotes($this->path))->versions());
    }

    public function test_it_returns_the_notes_for_a_version(): void
    {
        $notes = (new ReleaseNotes($this->path))->for('0.2.0');

        $this->assertStringContainsString("don't open sync PRs that change nothing", $notes);
    }

    public function test_a_section_stops_at_the_next_release(): void
    {
        $notes = (new ReleaseNotes($this->path))->for('0.3.0');

        $this->assertStringContainsString('report the build commit', $notes);
        $this->assertStringNotContainsString('open sync PRs', $notes);
    }

    public function test_it_keeps_the_subheadings_inside_a_release(): void
    {
        $this->assertStringContainsString('### Features', (new ReleaseNotes($this->path))->for('0.3.0'));
    }

    public function test_it_accepts_a_tag_name(): void
    {
        $this->assertNotNull((new ReleaseNotes($this->path))->for('v0.3.0'));
    }

    public function test_it_returns_null_for_an_undocumented_version(): void
    {
        $this->assertNull((new ReleaseNotes($this->path))->for('9.9.9'));
    }

    public function test_a_missing_changelog_documents_nothing(): void
    {
        $notes = new ReleaseNotes($this->path.'.nope');

        $this->assertFalse($notes->exists());
        $this->assertSame([], $notes->versions());
        $this->assertNull($notes->for('0.3.0'));
    }
}
