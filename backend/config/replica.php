<?php

return [
    // Standby database: a second, independent database that is kept identical to the main one.
    'enabled' => (bool) env('REPLICA_ENABLED', false),

    // The connection the app normally uses (DB_CONNECTION). Captured here because failover switches database.default.
    'primary' => env('DB_CONNECTION', 'sqlite'),

    // Driver of the standby; defaults to the same as the main database.
    'driver' => env('REPLICA_DB_CONNECTION', env('DB_CONNECTION', 'sqlite')),

    // Standby connection settings (anything left empty is inherited from the main connection of the same driver).
    // The standby MUST be a different database (other name, file or host) than the main one.
    'db' => [
        'url' => env('REPLICA_DB_URL'),
        'host' => env('REPLICA_DB_HOST'),
        'port' => env('REPLICA_DB_PORT'),
        'database' => env('REPLICA_DB_DATABASE'),
        'username' => env('REPLICA_DB_USERNAME'),
        'password' => env('REPLICA_DB_PASSWORD'),
    ],

    // Rows per page while copying.
    'page' => 500,

    // Not copied: per-database migration state and disposable cache tables.
    'exclude' => ['migrations', 'cache', 'cache_locks'],
];
