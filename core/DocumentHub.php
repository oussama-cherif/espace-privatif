<?php

require_once __DIR__ . '/Database.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class DocumentHub implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private array $abonnements = [];
    private PDO $db;

    public function __construct(LoopInterface $loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->db      = Database::getConnection();

        $loop->addPeriodicTimer(2, function () {
            $this->verifierStatuts();
        });
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        if (!isset($data['type'])) {
            return;
        }

        if ($data['type'] === 'subscribe' && isset($data['document_id'])) {
            $docId = (int) $data['document_id'];
            $this->abonnements[$docId][] = $from;
            echo "Navigateur abonné au document #" . $docId . PHP_EOL;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);

        foreach ($this->abonnements as $docId => $conns) {
            $this->abonnements[$docId] = array_filter(
                $conns,
                fn($c) => $c !== $conn
            );
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    private function verifierStatuts(): void
    {
        if (empty($this->abonnements)) {
            return;
        }

        $ids  = array_keys($this->abonnements);
        $in   = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $this->db->prepare("SELECT id, status FROM documents WHERE id IN ($in)");
            $stmt->execute($ids);
        } catch (\PDOException $e) {
            Database::reset();
            $this->db = Database::getConnection();
            $stmt = $this->db->prepare("SELECT id, status FROM documents WHERE id IN ($in)");
            $stmt->execute($ids);
        }

        foreach ($stmt->fetchAll() as $doc) {
            if ($doc['status'] === 'SIGNED_VALIDATED') {
                $this->diffuserStatut((int) $doc['id'], $doc['status']);
                unset($this->abonnements[(int) $doc['id']]);
            }
        }
    }

    private function diffuserStatut(int $documentId, string $statut): void
    {
        $message = json_encode([
            'type'        => 'status_update',
            'document_id' => $documentId,
            'status'      => $statut,
        ]);

        foreach ($this->abonnements[$documentId] ?? [] as $client) {
            $client->send($message);
        }

        echo "Statut diffusé au navigateur : document #" . $documentId . " → " . $statut . PHP_EOL;
    }
}
