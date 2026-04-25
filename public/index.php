<?php

require_once __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

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

    case '/document/pdf':
        require_once __DIR__ . '/../controllers/DocumentController.php';
        (new DocumentController())->servir();
        break;

    case '/signer/soumettre':
        require_once __DIR__ . '/../controllers/SignatureController.php';
        (new SignatureController())->soumettre();
        break;

    case '/document/confirmation':
        $documentId = (int) ($_GET['doc'] ?? 0);
        require __DIR__ . '/../views/confirmation.php';
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
        break;
}