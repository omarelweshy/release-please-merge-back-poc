<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | Maintained by release-please. Do not edit by hand -- the annotation
    | comment below is what the release PR rewrites.
    |
    */

    'version' => '0.3.0', // x-release-please-version

    /*
    |--------------------------------------------------------------------------
    | Changelog
    |--------------------------------------------------------------------------
    |
    | Where `app:changelog` reads release notes from. Pointed at the file
    | release-please maintains; overridable so a build that ships without one,
    | or a test, can say so.
    |
    */

    'changelog' => base_path('CHANGELOG.md'),

];
