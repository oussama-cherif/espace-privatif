<?php

require_once __DIR__ . '/../vendor/autoload.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/signer':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->signer();
        break;

    case '/document':
        require_once __DIR__ . '/../controllers/DocumentController.php';
        (new DocumentController())->afficher();
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
        break;
}