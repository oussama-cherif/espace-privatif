# API — ESPACE-PRIVATIF

## Endpoints HTTP

Toutes les routes passent par `public/index.php` (front controller).

### GET /signer?token={jwt}

Authentification du locataire via token JWT.

- Valide la signature cryptographique du JWT
- Vérifie que le token n'est pas expiré et n'a pas déjà été utilisé
- Crée une session PHP et redirige vers `/document`

Réponses :
- `302` → redirection vers `/document` (succès)
- `403` → token invalide, expiré ou déjà utilisé

---

### GET /document

Affiche l'interface de signature (PDF + canvas).

- Vérifie la session active
- Affiche le document selon son statut : formulaire de signature, message d'attente, ou confirmation

Réponses :
- `200` → page HTML
- `403` → session invalide ou expirée

---

### GET /document/pdf

Sert le fichier PDF original en streaming.

- Vérifie la session active
- Lit le fichier depuis `storage/` (hors public)
- Renvoie le contenu avec `Content-Type: application/pdf`

Réponses :
- `200` → contenu PDF
- `403` → session invalide
- `404` → fichier introuvable

---

### POST /signer/soumettre

Soumet la signature du locataire.

Paramètres POST :
- `signature_data` — image PNG de la signature en base64 (`data:image/png;base64,...`)
- `hash_document` — hash SHA-256 du document affiché
- `csrf_token` — token CSRF de la session

Traitement :
1. Valide CSRF, session, signature et hash
2. Transaction MySQL avec `SELECT FOR UPDATE`
3. Enregistre la signature en base et sur disque
4. Génère le PDF signé
5. Envoie les mails
6. Notifie SOTHIS via WebSocket
7. Détruit la session

Réponses :
- `302` → redirection vers `/document/confirmation?doc={id}` (succès)
- `400` → signature manquante
- `403` → CSRF ou session invalide
- `409` → document déjà signé ou hash incorrect
- `500` → erreur serveur

---

### GET /document/confirmation?doc={id}

Page de confirmation post-signature. Se connecte via WebSocket au port 8080 pour recevoir la validation SOTHIS en temps réel.

---

## WebSocket — DocumentHub (port 8080)

Serveur Ratchet géré par `bin/websocket-server.php`.

### Message client → serveur

```json
{ "type": "subscribe", "document_id": 1 }
```

S'abonne aux mises à jour de statut pour un document.

### Message serveur → client

```json
{ "type": "status_update", "document_id": 1, "status": "SIGNED_VALIDATED" }
```

Diffusé quand DocumentHub détecte un changement de statut (poll DB toutes les 2 secondes).

---

## WebSocket — Mock SOTHIS (port 8081)

Serveur Ratchet géré par `mock/sothis_ws_server.php`. Simule le serveur WebSocket de SOTHIS.

### Message ESPACE-PRIVATIF → SOTHIS

```json
{
  "type": "signature_soumise",
  "document_id": 1,
  "metadonnees": {
    "locataire": "Sophie Martin",
    "email": "sophie.martin@email.fr",
    "ip": "127.0.0.1",
    "hash": "a3f1c2...",
    "signed_at": "2026-04-25 17:00:00"
  }
}
```

### Message SOTHIS → ESPACE-PRIVATIF

```json
{ "type": "validation_ok", "document_id": 1, "status": "SIGNED_VALIDATED" }
```
