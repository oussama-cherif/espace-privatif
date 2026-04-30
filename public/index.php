<?php

require_once __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (!isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key] = trim($value);
        }
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

    case '/document/telecharger':
        require_once __DIR__ . '/../controllers/DocumentController.php';
        (new DocumentController())->telecharger();
        break;

    case '/document/confirmation':
        $documentId = (int) ($_GET['doc'] ?? 0);
        $appConfig  = require __DIR__ . '/../config/app.php';
        $wsUrl      = $appConfig['ws_url'];
        require __DIR__ . '/../views/confirmation.php';
        break;

    case '/test/start':
        require_once __DIR__ . '/../core/Database.php';
        $documentId = 1;
        $db         = Database::getConnection();
        $db->prepare('UPDATE documents SET status = ? WHERE id = ?')->execute(['PENDING_SIGNATURE', $documentId]);
        $db->prepare('UPDATE tokens SET used = 1 WHERE document_id = ? AND used = 0')->execute([$documentId]);

        $stmt = $db->prepare('SELECT locataire_id FROM documents WHERE id = ?');
        $stmt->execute([$documentId]);
        $row = $stmt->fetch();

        $appConfig  = require __DIR__ . '/../config/app.php';
        $expiration = time() + 48 * 3600;
        $payload    = [
            'document_id'  => $documentId,
            'locataire_id' => (int) $row['locataire_id'],
            'exp'          => $expiration,
            'iat'          => time(),
        ];
        $tokenString = \Firebase\JWT\JWT::encode($payload, $appConfig['jwt_secret'], $appConfig['jwt_algo']);

        $db->prepare(
            'INSERT INTO tokens (document_id, locataire_id, token, expire_at, used)
             VALUES (:document_id, :locataire_id, :token, FROM_UNIXTIME(:expire_at), 0)'
        )->execute([
            'document_id'  => $documentId,
            'locataire_id' => (int) $row['locataire_id'],
            'token'        => $tokenString,
            'expire_at'    => $expiration,
        ]);

        header('Location: /signer?token=' . $tokenString);
        exit;

    default:
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
        break;
}