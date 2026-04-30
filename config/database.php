<?php

return [
    'host'     => getenv('DB_HOST')     ?: ($_ENV['DB_HOST']     ?? '127.0.0.1'),
    'port'     => (int) (getenv('DB_PORT') ?: ($_ENV['DB_PORT']  ?? 3306)),
    'dbname'   => getenv('DB_NAME')     ?: ($_ENV['DB_NAME']     ?? 'espace_privatif'),
    'user'     => getenv('DB_USER')     ?: ($_ENV['DB_USER']     ?? 'root'),
    'password' => getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? ''),
    'charset'  => 'utf8mb4',
];
