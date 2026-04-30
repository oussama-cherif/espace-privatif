<?php

return [
    'host'      => getenv('MAIL_HOST')      ?: ($_ENV['MAIL_HOST']      ?? 'smtp.gmail.com'),
    'port'      => (int) (getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT']    ?? 587)),
    'username'  => getenv('MAIL_USERNAME')  ?: ($_ENV['MAIL_USERNAME']  ?? ''),
    'password'  => getenv('MAIL_PASSWORD')  ?: ($_ENV['MAIL_PASSWORD']  ?? ''),
    'from'      => getenv('MAIL_FROM')      ?: ($_ENV['MAIL_FROM']      ?? ''),
    'from_name' => getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'Espace Privatif'),
];
