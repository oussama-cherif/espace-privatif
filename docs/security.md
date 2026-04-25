# Sécurité — ESPACE-PRIVATIF

## Conformité légale

### RGPD

- Pas de stockage de mot de passe : l'authentification par token JWT évite de créer un compte locataire
- Minimisation des données : seules les métadonnées nécessaires à la preuve de signature sont conservées (IP, user-agent, timestamp, hash)
- Traçabilité : table `audit_log` horodatée pour chaque action sensible
- Droit à l'oubli : les cascades `ON DELETE CASCADE` permettent la suppression complète des données d'un locataire

### eIDAS (règlement UE 910/2014)

Niveau choisi : **signature électronique avancée**

Suffisant pour un bail ou un état des lieux en France. Ne nécessite pas de certificat qualifié (coûteux). Répond aux 4 exigences du niveau avancé :

1. Liée au signataire de manière univoque → token JWT nominatif usage unique
2. Permet d'identifier le signataire → nom, email, IP, user-agent stockés
3. Créée avec des données sous le contrôle exclusif du signataire → session sécurisée
4. Permet de détecter toute modification → hash SHA-256 du document vérifié à la signature

## Mesures techniques

### Authentification par token JWT

- Token signé HMAC-SHA256 avec clé secrète (minimum 32 caractères)
- Contient : `document_id`, `locataire_id`, `exp` (48h)
- Double validation : cryptographique d'abord (sans DB), puis usage unique en base (`used = 0`)
- Session PHP détruite immédiatement après la signature

### Protection CSRF

- Token aléatoire `bin2hex(random_bytes(32))` généré à l'ouverture du formulaire
- Stocké en session, injecté dans le formulaire en champ caché
- Comparé avec `hash_equals()` à la soumission (résistant aux timing attacks)

### Intégrité du document

- Hash SHA-256 calculé à la réception du PDF depuis SOTHIS
- Recalculé et comparé à la soumission de la signature
- Si le hash ne correspond pas, la signature est bloquée (le document a été modifié)

### Accès aux fichiers

- Le dossier `storage/` est hors de `public/` : aucun fichier n'est accessible par URL directe
- Les PDFs sont servis par PHP (`DocumentController::servir()`) après vérification de session
- Un PDF ne peut pas être téléchargé sans session valide

### Requêtes préparées

- Toutes les requêtes SQL utilisent PDO avec `ATTR_EMULATE_PREPARES = false`
- Les requêtes préparées sont envoyées au serveur MySQL, pas simulées par PHP
- Protection native contre les injections SQL

### Audit

Chaque action sensible est enregistrée dans `audit_log` :
- Réception du document depuis SOTHIS
- Génération du lien de signature
- Ouverture du document par le locataire
- Signature enregistrée
- Validation par SOTHIS

## Ce qui serait ajouté en production

- HTTPS obligatoire (certificat Let's Encrypt ou certificat interne Realsoft)
- Rate limiting sur les endpoints d'authentification
- `Secure`, `HttpOnly`, `SameSite=Strict` sur les cookies de session
- En-têtes HTTP de sécurité : `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`
