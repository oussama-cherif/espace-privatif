#!/bin/bash

mkdir -p /var/www/html/config

cat > /var/www/html/config/database.php <<'PHPEOF'
<?php
return [
    'host'     => getenv('DB_HOST')     ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME')     ?: 'espace_privatif',
    'user'     => getenv('DB_USER')     ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];
PHPEOF

exec php mock/sothis_ws_server.php
