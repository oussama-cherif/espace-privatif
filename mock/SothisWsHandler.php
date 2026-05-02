<?php

require_once __DIR__ . '/../core/Database.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SothisWsHandler implements MessageComponentInterface
{
    private \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "Connexion reçue depuis ESPACE-PRIVATIF" . PHP_EOL;
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        if (!isset($data['type'])) {
            return;
        }

        if ($data['type'] === 'signature_soumise') {
            $documentId = (int) $data['document_id'];
            echo "Signature reçue pour le document #" . $documentId . " — validation en cours..." . PHP_EOL;

            sleep(2);

            Database::reset();
            $db   = Database::getConnection();
            $stmt = $db->prepare('UPDATE documents SET status = :status WHERE id = :id');
            $stmt->execute(['status' => 'SIGNED_VALIDATED', 'id' => $documentId]);

            $log = $db->prepare(
                'INSERT INTO audit_log (document_id, action, details, ip_address)
                 VALUES (:document_id, :action, :details, :ip)'
            );
            $log->execute([
                'document_id' => $documentId,
                'action'      => 'DOCUMENT_VALIDATED',
                'details'     => 'Validation effectuée par SOTHIS (mock)',
                'ip'          => '127.0.0.1',
            ]);

            $from->send(json_encode([
                'type'        => 'validation_ok',
                'document_id' => $documentId,
                'status'      => 'SIGNED_VALIDATED',
            ]));

            echo "Document #" . $documentId . " validé et confirmé." . PHP_EOL;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Erreur : " . $e->getMessage() . PHP_EOL;
        $conn->close();
    }
}
