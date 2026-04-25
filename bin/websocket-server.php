<?php

/**
 * Serveur WebSocket ESPACE-PRIVATIF — port 8080
 *
 * Usage : php bin/websocket-server.php
 *
 * Le navigateur s'y connecte après signature pour recevoir
 * les mises à jour de statut du document en temps réel.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/DocumentHub.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;

$loop   = Loop::get();
$hub    = new DocumentHub($loop);

$server = new IoServer(
    new HttpServer(new WsServer($hub)),
    new \React\Socket\SocketServer('0.0.0.0:8080', [], $loop),
    $loop
);

echo "Serveur WebSocket ESPACE-PRIVATIF démarré sur le port 8080" . PHP_EOL;
$loop->run();
