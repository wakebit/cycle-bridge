<?php

declare(strict_types=1);

use function DI\env;

return [
    // Environment variables
    'debug'         => env('APP_DEBUG', 'true'),

    // Database
    'db.connection' => env('DB_CONNECTION', 'sqlite'),
    'db.host'       => env('DB_HOST', 'localhost'),
    'db.database'   => env('DB_DATABASE', ''),
    'db.username'   => env('DB_USERNAME', ''),
    'db.password'   => env('DB_PASSWORD', ''),
];
