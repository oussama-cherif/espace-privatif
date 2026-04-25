<?php

/**
 * Serveur WebSocket mock SOTHIS — port 8081
 *
 * Usage : php mock/sothis_ws_server.php
 *
 * Simule le serveur WebSocket de SOTHIS.
 * Reçoit les données de signature depuis ESPACE-PRIVATIF,
 * valide le document et renvoie la confirmation.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/SothisWsHandler.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(new WsServer(new SothisWsHandler())),
    8081
);

echo "Serveur WebSocket SOTHIS (mock) démarré sur le port 8081" . PHP_EOL;
$server->run();
