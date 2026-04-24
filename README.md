# Espace-Privatif

Module de signature électronique web sécurisé, développé en PHP natif selon une architecture MVC.

Conçu pour s'interfacer avec le logiciel métier SOTHIS de Realsoft dans le cadre de la gestion de résidences touristiques.

## Fonctionnalités

- Réception de documents PDF depuis SOTHIS
- Authentification du locataire par lien sécurisé (token JWT usage unique)
- Signature électronique avancée (conforme eIDAS) avec capture de métadonnées
- Notification par mail (locataire + responsable de résidence)
- Communication en temps réel avec SOTHIS via WebSocket
- Gestion des états du document : en attente, signé non validé, signé validé
- Architecture multi-client, multi-locataire, multi-document

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.x natif, architecture MVC |
| Base de données | MySQL |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| WebSocket | Ratchet (PHP) |
| Signature | FPDI + TCPDF + OpenSSL |
| Mail | PHPMailer |
| Versioning | Git + GitHub |

## Installation

### Prérequis

- PHP 8.x
- MySQL 8.x
- Composer

### Étapes

```bash
git clone https://github.com/oussama-cherif/espace-privatif.git
cd espace-privatif
composer install
cp config/database.example.php config/database.php
# Renseigner les paramètres de connexion dans config/database.php
mysql -u root -p < database/schema.sql
```

Démarrer un serveur PHP local :

```bash
php -S localhost:8000 -t public/
```

## Architecture

```
espace-privatif/
├── controllers/    logique métier
├── models/         accès base de données
├── views/          templates HTML
├── config/         paramètres de configuration
├── public/         point d'entrée web (seul dossier exposé)
├── mock/           simulation de SOTHIS pour les tests
├── database/       scripts SQL
└── docs/           documentation technique
```

## Simulation SOTHIS

SOTHIS est le logiciel métier de Realsoft. Dans le cadre de ce projet, ESPACE-PRIVATIF communique avec un mock de SOTHIS pour illustrer les flux attendus. En production, l'intégration se ferait via les endpoints HTTP et WebSocket définis dans ce dépôt.

## Documentation

La documentation technique complète est disponible dans le dossier [`/docs`](docs/).

## Conformité

- Signature électronique avancée conforme au règlement eIDAS (UE 910/2014)
- Traitement des données conforme au RGPD
- Journalisation complète des actions de signature (table `audit_log`)
