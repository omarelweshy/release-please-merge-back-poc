<?php

namespace App\Console\Commands;

use App\Support\BuildInfo;
use App\Support\ReleaseNotes;
use Illuminate\Console\Command;

/**
 * Prints the release notes for the version this instance is running.
 *
 * `app:version` answers "what is running here". This answers the follow-up --
 * "and what is in it" -- from the CHANGELOG.md baked into the artifact, so it
 * works on a box with a shell and no browser.
 *
 * With no argument it reports the deployed version, which is the case you
 * actually have during an incident. A version can be named explicitly to read
 * back over previous releases.
 *
 * --raw prints the notes alone so they can be piped or pasted into a ticket.
 */
class ShowChangelog extends Command
{
    protected $signature = 'app:changelog
                            {version? : The version to show; defaults to the deployed one}
                            {--raw : Print the notes alone, without the heading}';

    protected $description = 'Show the release notes for a deployed version';

    public function handle(): int
    {
        $notes = ReleaseNotes::default();

        if (! $notes->exists()) {
            $this->components->error('No CHANGELOG.md in this build.');

            return self::FAILURE;
        }

        $version = (string) ($this->argument('version') ?? BuildInfo::version());
        $section = $notes->for($version);

        if ($section === null) {
            $this->components->error("The changelog has no entry for {$version}.");
            $this->listKnownVersions($notes);

            return self::FAILURE;
        }

        if (! $this->option('raw')) {
            $this->components->info("Release notes for {$version}");
        }

        $this->line($section);

        return self::SUCCESS;
    }

    /**
     * A version that is missing is usually a typo, or a build whose changelog
     * predates the release being asked about. Showing the most recent handful
     * tells those two apart without a second command.
     */
    private function listKnownVersions(ReleaseNotes $notes): void
    {
        $known = array_slice($notes->versions(), 0, 5);

        if ($known === []) {
            return;
        }

        $this->line('  Documented versions: '.implode(', ', $known));
    }
}
