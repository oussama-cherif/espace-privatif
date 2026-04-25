# Gestion de la concurrence — ESPACE-PRIVATIF

## Le problème

Si deux personnes ouvrent le même lien de signature simultanément (deux onglets, deux appareils), deux requêtes peuvent arriver en même temps sur `POST /signer/soumettre`. Sans protection, les deux pourraient lire `status = PENDING_SIGNATURE` et signer le même document.

## Solution : transaction + SELECT FOR UPDATE

Dans `SignatureController::soumettre()`, la partie critique est enveloppée dans une transaction MySQL :

```php
$db->beginTransaction();

$lock = $db->prepare('SELECT id, status FROM documents WHERE id = :id FOR UPDATE');
$lock->execute(['id' => $documentId]);
$row = $lock->fetch();

if ($row['status'] !== 'PENDING_SIGNATURE') {
    $db->rollBack();
    // Erreur 409 : document déjà signé
    return;
}

// INSERT signatures + UPDATE status → SIGNED_UNVALIDATED
$db->commit();
```

`FOR UPDATE` pose un verrou exclusif InnoDB sur la ligne. Toute autre transaction qui tente un `SELECT FOR UPDATE` sur la même ligne attend que la première ait commité. Une fois le verrou obtenu, on re-vérifie le statut à l'intérieur de la transaction.

## Séquence en cas de conflit

```
Requête A                          Requête B
    │                                  │
    ├── BEGIN TRANSACTION               │
    ├── SELECT FOR UPDATE ─── verrou posé
    │                                  ├── BEGIN TRANSACTION
    │                                  ├── SELECT FOR UPDATE ─── ATTEND
    ├── status = PENDING ? OUI         │
    ├── INSERT signatures              │
    ├── UPDATE status → SIGNED_UNVALIDATED
    ├── COMMIT ─────────── verrou libéré
    │                                  │
    │                                  ├── reprend, lit status = SIGNED_UNVALIDATED
    │                                  ├── status = PENDING ? NON
    │                                  ├── ROLLBACK
    │                                  └── Erreur 409
```

## Machine à états du document

Les transitions sont strictement contrôlées. Une transition non autorisée est bloquée côté serveur.

```
PENDING_SIGNATURE
      │
      │ (signature locataire)
      ▼
SIGNED_UNVALIDATED
      │
      │ (validation SOTHIS)
      ▼
SIGNED_VALIDATED
```

| Transition | Autorisée |
|------------|-----------|
| PENDING_SIGNATURE → SIGNED_UNVALIDATED | Oui |
| SIGNED_UNVALIDATED → SIGNED_VALIDATED | Oui |
| PENDING_SIGNATURE → SIGNED_VALIDATED | Non (saut d'état) |
| SIGNED_VALIDATED → PENDING_SIGNATURE | Non (retour arrière) |
| SIGNED_UNVALIDATED → PENDING_SIGNATURE | Non (retour arrière) |

La classe `DocumentStateMachine` dans `core/DocumentStateMachine.php` encapsule ces règles et est couverte par des tests unitaires PHPUnit.

## Protection côté navigateur

En parallèle du verrou DB, DocumentHub diffuse les changements de statut au navigateur via WebSocket. Si un locataire a le formulaire ouvert pendant qu'un autre signe, sa page se met à jour en temps réel et le formulaire devient inutilisable.
