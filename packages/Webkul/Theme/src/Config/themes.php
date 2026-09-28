<?php

return [
    /*
     * Default / Active Theme ID.
     */
    'active' => env('APP_THEME', 'base'),

    /*
     * Fallback Theme ID when a view is missing in the active theme.
     */
    'fallback' => 'base',

    /*
     * Themes scan paths.
     */
    'paths' => [
        base_path('themes'),
    ],
];
