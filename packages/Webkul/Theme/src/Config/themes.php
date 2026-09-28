<?php

return [
    /*
     * Default / Active Theme ID.
     */
    'active' => env('APP_THEME', 'default'),

    /*
     * Fallback Theme ID when a view is missing in the active theme.
     */
    'fallback' => 'default',

    /*
     * Themes scan paths.
     */
    'paths' => [
        base_path('themes'),
    ],
];
