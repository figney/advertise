<?php

return [
    'route' => [
        'prefix'     => env('ADMIN_ROUTE_PREFIX', 'admin').'/logs',
        'namespace'  => 'Dcat\LogViewer',
        'middleware' => ['web', 'admin'],
    ],

    'directory' => storage_path('logs'),

    'search_page_items' => 500,

    'page_items' => 10,
];
