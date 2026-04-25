# Architecture — ESPACE-PRIVATIF

## Stack technique

| Couche | Technologie | Justification |
|--------|-------------|---------------|
| Backend | PHP 8.3 natif, MVC | Aligné avec l'environnement Realsoft, maîtrise complète de chaque couche |
| Base de données | MySQL 8.0 | Imposé par SOTHIS, transactions InnoDB pour la concurrence |
| Frontend | HTML5, CSS3, JavaScript vanilla | Pas de dépendance inutile pour un module ciblé |
| WebSocket | Ratchet (cboden/ratchet) | Bibliothèque PHP mature pour les connexions temps réel |
| Client WebSocket | textalk/websocket | Client PHP pour la communication ESPACE-PRIVATIF vers SOTHIS |
| Signature PDF | setasign/fpdi + tecnickcom/tcpdf | Import du PDF original + génération de la page de certification |
| Authentification | firebase/php-jwt | Standard JWT, usage unique, sans stockage de mot de passe |
| Mail | phpmailer/phpmailer | Envoi SMTP fiable avec support TLS |
| Conteneurisation | Docker Compose | Déploiement reproductible en une commande |

## Pourquoi PHP natif et pas Laravel ou Symfony ?

Realsoft travaille avec PHP. Pour un module de périmètre fonctionnel précis comme celui-ci, un framework apporterait plus de complexité que de valeur. PHP natif structuré en MVC permet de maîtriser chaque couche : sécurité, logique métier, architecture. Chaque décision est explicite et défendable.

## Pattern Front Controller

Toutes les requêtes HTTP arrivent dans `public/index.php`. C'est le seul fichier exposé au navigateur. Le reste du code est hors de `public/`, inaccessible directement par URL.

```
GET /signer?token=xxx  →  public/index.php  →  AuthController::signer()
GET /document          →  public/index.php  →  DocumentController::afficher()
POST /signer/soumettre →  public/index.php  →  SignatureController::soumettre()
```

## Structure des dossiers

```
/
├── public/          ← seul dossier exposé au web
│   ├── index.php    ← front controller
│   ├── css/
│   └── js/
├── controllers/     ← logique métier
├── models/          ← accès base de données
├── views/           ← templates HTML
├── core/            ← services transversaux (DB, PDF, WebSocket, Mail)
├── config/          ← configuration (credentials gitignorés)
├── mock/            ← simulation de SOTHIS
├── bin/             ← serveurs WebSocket
├── storage/         ← fichiers générés (hors public)
├── database/        ← schema et seed SQL
├── docs/            ← documentation technique
└── tests/           ← tests unitaires PHPUnit
```

## Flux principal

```
SOTHIS
  ├── upload PDF ─────────────────────→ storage/documents/{id}/
  └── génère token JWT + envoie mail ─→ locataire

LOCATAIRE
  ├── clique le lien ──────────────────→ AuthController (valide JWT + crée session)
  ├── visualise le PDF ────────────────→ DocumentController (sert depuis storage/)
  └── signe (canvas) ──────────────────→ SignatureController
          ├── SELECT FOR UPDATE (verrou DB)
          ├── INSERT signatures
          ├── UPDATE status → SIGNED_UNVALIDATED
          ├── génère PDF signé (FPDI + TCPDF)
          ├── envoie mails (locataire + gestionnaire)
          └── notifie SOTHIS via WebSocket

SOTHIS (mock)
  └── reçoit notification ─────────────→ UPDATE status → SIGNED_VALIDATED

NAVIGATEUR
  └── WebSocket port 8080 ─────────────→ DocumentHub poll DB toutes les 2s
                                          └── affiche "Document validé" en vert
```

## Infrastructure Docker

4 services lancés par `docker-compose up --build` :

| Service | Rôle | Port |
|---------|------|------|
| `app` | Apache + PHP 8.3 | 8000 |
| `db` | MySQL 8.0 (schema + seed auto) | 3306 |
| `websocket` | DocumentHub Ratchet | 8080 |
| `sothis` | Mock SOTHIS WebSocket | 8081 |

Les variables d'environnement sont injectées par Docker depuis `.env`. Les valeurs sensibles (credentials DB, SMTP, JWT secret) ne sont jamais commitées.
