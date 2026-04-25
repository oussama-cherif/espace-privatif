<?php

use WebSocket\Client;

class SothisNotifier
{
    private string $url;

    public function __construct()
    {
        $this->url = $_ENV['SOTHIS_WS_URL'] ?? getenv('SOTHIS_WS_URL') ?: 'ws://127.0.0.1:8081';
    }

    public function notifierSignature(int $documentId, array $metadonnees): bool
    {
        try {
            $client = new Client($this->url);
            $client->send(json_encode([
                'type'          => 'signature_soumise',
                'document_id'   => $documentId,
                'metadonnees'   => $metadonnees,
            ]));

            $reponse = json_decode($client->receive(), true);
            $client->close();

            return isset($reponse['type']) && $reponse['type'] === 'validation_ok';
        } catch (\Exception $e) {
            error_log('SothisNotifier erreur : ' . $e->getMessage());
            return false;
        }
    }
}
