<?php

// Usage : php mock/reset_document.php <document_id>

require_once __DIR__ . '/../core/Database.php';

$id = (int) ($argv[1] ?? 4);
$db = Database::getConnection();

$db->prepare('UPDATE documents SET status = ? WHERE id = ?')->execute(['PENDING_SIGNATURE', $id]);
$db->prepare('UPDATE tokens SET used = 0, used_at = NULL WHERE document_id = ?')->execute([$id]);

echo "Document #" . $id . " remis en PENDING_SIGNATURE." . PHP_EOL;
