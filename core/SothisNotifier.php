<?php

use WebSocket\Client;

class SothisNotifier
{
    private string $url;

    public function __construct(string $url = 'ws://127.0.0.1:8081')
    {
        $this->url = $url;
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
