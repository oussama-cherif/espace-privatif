<?php

return [
    'jwt_secret'       => $_ENV['JWT_SECRET']  ?? 'changez-cette-cle-en-production-32-caracteres-min',
    'jwt_algo'         => 'HS256',
    'session_lifetime' => 3600,
    'base_url'         => $_ENV['APP_BASE_URL'] ?? 'http://localhost:8000',
];
