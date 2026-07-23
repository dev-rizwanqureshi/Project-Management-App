<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Filtering
    |--------------------------------------------------------------------------
    |
    | Keep both values null to expose all named routes to the frontend. As the
    | app grows, tighten this list with `only`, `except`, or named groups.
    |
    */

    'only' => null,

    'except' => null,

    'groups' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Metadata
    |--------------------------------------------------------------------------
    |
    | Set to true to include all route middleware in generated Ziggy metadata,
    | or provide an array of middleware names to include only those entries.
    |
    */

    'middleware' => false,
];
