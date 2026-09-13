<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Prints the deployed version.
 *
 * The same value the /version endpoint reports, reachable without the HTTP
 * stack -- which is what you want when you have a shell on a box and the
 * question is "what is actually running here", possibly because the app is not
 * serving.
 *
 * --short prints the bare version so it can be piped.
 */
class ShowVersion extends Command
{
    protected $signature = 'app:version {--short : Print only the version number}';

    protected $description = 'Show the deployed application version';

    public function handle(): int
    {
        $version = config('version.version');

        if ($this->option('short')) {
            $this->line($version);

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Version', $version);
        $this->components->twoColumnDetail('Environment', config('app.env'));

        return self::SUCCESS;
    }
}
