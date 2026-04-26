# Espace-Privatif

Module de signature électronique web sécurisé, développé en PHP natif selon une architecture MVC.

Conçu pour s'interfacer avec le logiciel métier SOTHIS de Realsoft dans le cadre de la gestion de résidences touristiques.

## Fonctionnalités

- Réception de documents PDF depuis SOTHIS
- Authentification du locataire par lien sécurisé (token JWT usage unique)
- Signature électronique avancée conforme eIDAS avec capture de métadonnées
- Notification par mail (locataire + responsable de résidence)
- Communication en temps réel avec SOTHIS via WebSocket
- Gestion des états du document : en attente, signé non validé, signé validé
- Architecture multi-client, multi-locataire, multi-document
- Tests unitaires PHPUnit (token JWT, machine à états, hash SHA-256)

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.3 natif, architecture MVC |
| Base de données | MySQL 8.0 |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| WebSocket | Ratchet (PHP) |
| Signature PDF | FPDI + TCPDF |
| Authentification | JWT (firebase/php-jwt) |
| Mail | PHPMailer |
| Conteneurisation | Docker Compose |

## Installation avec Docker (recommandé)

### Prérequis

- Docker Desktop

### Démarrage

```bash
git clone https://github.com/oussama-cherif/espace-privatif.git
cd espace-privatif
cp .env.example .env
# Renseigner les valeurs dans .env (SMTP Gmail, JWT secret)
docker-compose up --build
```

L'application est accessible sur [http://localhost:8000](http://localhost:8000).

Les 4 services démarrent automatiquement :
- Application PHP + Apache (port 8000)
- MySQL avec schema et données de test chargés (port 3306)
- Serveur WebSocket navigateur (port 8080)
- Mock SOTHIS WebSocket (port 8081)

### Générer un lien de signature pour tester

```bash
docker exec espace-privatif-app-1 php mock/generate_token.php 1
```

### Remettre un document en attente de signature

```bash
docker exec espace-privatif-app-1 php mock/reset_document.php 1
```

## Installation manuelle (sans Docker)

### Prérequis

- PHP 8.3
- MySQL 8.0
- Composer

### Étapes

```bash
git clone https://github.com/oussama-cherif/espace-privatif.git
cd espace-privatif
composer install
cp .env.example .env
# Renseigner les valeurs dans .env
mysql -u root -p < database/schema.sql
mysql -u root -p espace_privatif < database/seed.sql
```

Démarrer les serveurs dans 3 terminaux séparés :

```bash
# Terminal 1 — Application web
php -S localhost:8000 -t public/

# Terminal 2 — WebSocket navigateur
php bin/websocket-server.php

# Terminal 3 — Mock SOTHIS
php mock/sothis_ws_server.php
```

Générer un lien de test :

```bash
php mock/generate_token.php 1
```

Remettre un document en attente de signature pour retester :

```bash
php mock/reset_document.php 1
```

## Tests unitaires

```bash
vendor/bin/phpunit
```

14 tests couvrant la validation JWT, la machine à états du document et la vérification du hash SHA-256.

## Simulation SOTHIS

SOTHIS est le logiciel métier de Realsoft. Dans ce projet, ESPACE-PRIVATIF communique avec un mock de SOTHIS pour illustrer les flux attendus. En production, l'intégration se brancherait sur les endpoints WebSocket et HTTP définis dans ce dépôt.

## Documentation technique

| Document | Contenu |
|----------|---------|
| [architecture.md](docs/architecture.md) | Stack, MVC, flux principal, Docker |
| [security.md](docs/security.md) | eIDAS, RGPD, JWT, CSRF, audit |
| [concurrency.md](docs/concurrency.md) | SELECT FOR UPDATE, machine à états |
| [api.md](docs/api.md) | Endpoints HTTP et protocole WebSocket |
| [database.md](docs/database.md) | Schéma, tables, contraintes |

## Conformité

- Signature électronique avancée conforme au règlement eIDAS (UE 910/2014)
- Traitement des données conforme au RGPD
- Journalisation complète des actions dans la table `audit_log`
