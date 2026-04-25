# Base de données — ESPACE-PRIVATIF

## Schéma

Le schéma complet est dans `database/schema.sql`. Les données de test sont dans `database/seed.sql`.

## Tables

### clients

Les sociétés clientes de Realsoft (propriétaires de résidences).

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | Identifiant |
| nom | VARCHAR(150) | Nom de la société |
| email | VARCHAR(255) | Email du gestionnaire (destinataire des notifications) |
| telephone | VARCHAR(20) | |

---

### residences

Les résidences gérées par un client.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| client_id | FK → clients | Propriétaire de la résidence |
| nom | VARCHAR(150) | |
| adresse | VARCHAR(255) | |

---

### locataires

Les locataires rattachés à une résidence.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| residence_id | FK → residences | Résidence du locataire |
| nom | VARCHAR(100) | |
| prenom | VARCHAR(100) | |
| email | VARCHAR(255) | Destinataire du lien de signature et de la confirmation |
| telephone | VARCHAR(20) | |

---

### documents

Les PDFs envoyés par SOTHIS en attente de signature.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| locataire_id | FK → locataires | Signataire attendu |
| nom_fichier | VARCHAR(255) | Nom du fichier PDF |
| chemin | VARCHAR(500) | Chemin relatif depuis `storage/` |
| hash_sha256 | CHAR(64) | Empreinte du document à la réception — vérifiée à la signature |
| status | ENUM | `PENDING_SIGNATURE`, `SIGNED_UNVALIDATED`, `SIGNED_VALIDATED` |
| locked_by | INT UNSIGNED | Réservé pour verrouillage applicatif (SELECT FOR UPDATE utilisé à la place) |
| locked_at | DATETIME | |

---

### tokens

Les liens de signature générés par SOTHIS.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| document_id | FK → documents | |
| locataire_id | FK → locataires | |
| token | VARCHAR(500) | JWT complet encodé |
| expire_at | DATETIME | Date d'expiration (48h par défaut) |
| used | TINYINT(1) | 0 = valide, 1 = déjà utilisé |
| used_at | DATETIME | Date d'utilisation |

---

### signatures

Les données capturées au moment de la signature.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| document_id | FK → documents | |
| locataire_id | FK → locataires | |
| hash_document | CHAR(64) | Hash SHA-256 au moment de la signature (preuve eIDAS) |
| signature_image | TEXT | Chemin vers l'image PNG de la signature manuscrite |
| ip_address | VARCHAR(45) | IP du signataire (IPv4 et IPv6) |
| user_agent | VARCHAR(500) | Navigateur et OS du signataire |
| signed_at | DATETIME | Horodatage de la signature |

---

### audit_log

Journal de toutes les actions sensibles.

| Colonne | Type | Rôle |
|---------|------|------|
| id | INT UNSIGNED PK | |
| document_id | FK nullable | Document concerné |
| locataire_id | FK nullable | Locataire concerné |
| action | VARCHAR(100) | Ex: `DOCUMENT_SIGNED`, `DOCUMENT_VALIDATED` |
| details | TEXT | Description libre |
| ip_address | VARCHAR(45) | IP de l'auteur de l'action |
| created_at | DATETIME | Horodatage automatique |

## Contraintes de suppression

Toutes les clés étrangères ont `ON DELETE CASCADE`. La suppression d'un client supprime en cascade ses résidences, locataires, documents, tokens, signatures et entrées d'audit.

## Multi-entité

Le schéma supporte nativement :
- **Multi-client** : plusieurs sociétés clientes avec leur propre résidence
- **Multi-résidence** : un client peut avoir plusieurs résidences
- **Multi-locataire** : une résidence peut avoir plusieurs locataires
- **Multi-document** : un locataire peut avoir plusieurs documents à signer
