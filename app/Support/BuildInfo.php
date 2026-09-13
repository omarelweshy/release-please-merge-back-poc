<?php

namespace App\Support;

/**
 * What this instance was built from.
 *
 * The version alone is not enough to identify a deployment: several commits
 * carry the same version between releases, and a hotfix that has not been
 * tagged yet reports the previous one. The commit sha disambiguates.
 *
 * APP_COMMIT is expected to be baked in at build time. When it is missing --
 * a local checkout, or a pipeline that forgot to set it -- that is reported
 * honestly rather than guessed at.
 */
class BuildInfo
{
    public static function version(): string
    {
        return (string) config('version.version');
    }

    public static function commit(): ?string
    {
        $commit = env('APP_COMMIT');

        return $commit ? substr((string) $commit, 0, 8) : null;
    }

    /** @return array{version: string, commit: string|null} */
    public static function toArray(): array
    {
        return [
            'version' => self::version(),
            'commit' => self::commit(),
        ];
    }
}
