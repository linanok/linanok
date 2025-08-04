<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reserved Path Prefixes
    |--------------------------------------------------------------------------
    |
    | These prefixes should not be used as short paths because they may conflict
    | with frontend assets or backend routes.
    |
    */
    'public_path_prefixes' => [
        'admin',
        'filament',
        'livewire',
        'storage',
        'css',
        'js',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved Public Paths
    |--------------------------------------------------------------------------
    |
    | These are exact file paths that should never be used as slugs.
    |
    */
    'public_paths' => [
        '.htaccess',
        'favicon.ico',
        'frankenphp-worker.php',
        'index.php',
        'linanok.svg',
        'logo.svg',
        'robots.txt',
    ],
];
