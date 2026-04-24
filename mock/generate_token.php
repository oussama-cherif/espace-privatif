<?php

/**
 * Mock SOTHIS — génération du token JWT et du lien de signature
 *
 * Usage : php mock/generate_token.php <document_id>
 *
 * Simule ce que SOTHIS ferait en production :
 * 1. Créer un token JWT signé avec les identifiants du document et du locataire
 * 2. L'insérer en base avec une date d'expiration
 * 3. Afficher le lien (en production, SOTHIS enverrait ce lien par mail)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';

use Firebase\JWT\JWT;

if (empty($argv[1]) || !is_numeric($argv[1])) {
    echo "Usage : php mock/generate_token.php <document_id>" . PHP_EOL;
    exit(1);
}

$documentId = (int) $argv[1];
$config     = require __DIR__ . '/../config/app.php';
$db         = Database::getConnection();

$stmt = $db->prepare(
    'SELECT d.id, d.locataire_id, d.status, l.prenom, l.nom, l.email
     FROM documents d
     JOIN locataires l ON l.id = d.locataire_id
     WHERE d.id = :id'
);
$stmt->execute(['id' => $documentId]);
$document = $stmt->fetch();

if (!$document) {
    echo "Erreur : document introuvable (id=" . $documentId . ")." . PHP_EOL;
    exit(1);
}

if ($document['status'] !== 'PENDING_SIGNATURE') {
    echo "Erreur : ce document n'est pas en attente de signature (status=" . $document['status'] . ")." . PHP_EOL;
    exit(1);
}

$expiration = time() + 48 * 3600;

$payload = [
    'document_id'  => $documentId,
    'locataire_id' => $document['locataire_id'],
    'exp'          => $expiration,
    'iat'          => time(),
];

$tokenString = JWT::encode($payload, $config['jwt_secret'], $config['jwt_algo']);

$insertStmt = $db->prepare(
    'INSERT INTO tokens (document_id, locataire_id, token, expire_at, used)
     VALUES (:document_id, :locataire_id, :token, FROM_UNIXTIME(:expire_at), 0)'
);
$insertStmt->execute([
    'document_id'  => $documentId,
    'locataire_id' => $document['locataire_id'],
    'token'        => $tokenString,
    'expire_at'    => $expiration,
]);

$logStmt = $db->prepare(
    'INSERT INTO audit_log (document_id, locataire_id, action, details, ip_address)
     VALUES (:document_id, :locataire_id, :action, :details, :ip)'
);
$logStmt->execute([
    'document_id'  => $documentId,
    'locataire_id' => $document['locataire_id'],
    'action'       => 'TOKEN_GENERATED',
    'details'      => 'Lien de signature généré (mock SOTHIS)',
    'ip'           => '127.0.0.1',
]);

$lien = $config['base_url'] . '/signer?token=' . $tokenString;

echo "Token généré pour : " . $document['prenom'] . ' ' . $document['nom'] . " (" . $document['email'] . ")" . PHP_EOL;
echo "Expire le        : " . date('d/m/Y H:i', $expiration) . PHP_EOL;
echo PHP_EOL;
echo "Lien à envoyer par mail :" . PHP_EOL;
echo $lien . PHP_EOL;
