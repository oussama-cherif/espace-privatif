-- Données de test — ESPACE-PRIVATIF
-- A exécuter après schema.sql

SET NAMES utf8mb4;

-- -----------------------------------------------------
-- Clients
-- -----------------------------------------------------
INSERT INTO `clients` (`id`, `nom`, `email`, `telephone`) VALUES
(1, 'Résidences du Soleil', 'contact@residences-soleil.fr', '0491000001'),
(2, 'Groupe Azur Locations', 'admin@azur-locations.fr', '0493000002');

-- -----------------------------------------------------
-- Résidences
-- -----------------------------------------------------
INSERT INTO `residences` (`id`, `client_id`, `nom`, `adresse`) VALUES
(1, 1, 'Résidence Les Calanques', '12 avenue de la Mer, 13009 Marseille'),
(2, 1, 'Résidence Belle Vue', '3 rue des Pins, 06400 Cannes'),
(3, 2, 'Résidence Cap Bleu', '27 boulevard du Littoral, 06300 Nice');

-- -----------------------------------------------------
-- Locataires
-- -----------------------------------------------------
INSERT INTO `locataires` (`id`, `residence_id`, `nom`, `prenom`, `email`, `telephone`) VALUES
(1, 1, 'Martin', 'Sophie', 'sophie.martin@email.fr', '0601010101'),
(2, 1, 'Dupont', 'Karim', 'karim.dupont@email.fr', '0602020202'),
(3, 2, 'Bernard', 'Lucie', 'lucie.bernard@email.fr', '0603030303'),
(4, 3, 'Lemaire', 'Thomas', 'thomas.lemaire@email.fr', '0604040404');

-- -----------------------------------------------------
-- Documents (trois états différents pour tester)
-- -----------------------------------------------------
INSERT INTO `documents` (`id`, `locataire_id`, `nom_fichier`, `chemin`, `hash_sha256`, `status`) VALUES
(1, 1, 'bail_martin_sophie_2026.pdf', 'documents/1/bail_martin_sophie_2026.pdf',
    'a3f1c2d4e5b6a7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2', 'PENDING_SIGNATURE'),
(2, 2, 'etat_lieux_dupont_karim_2026.pdf', 'documents/2/etat_lieux_dupont_karim_2026.pdf',
    'b4e2d3f5a6c7b8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3', 'SIGNED_UNVALIDATED'),
(3, 3, 'bail_bernard_lucie_2026.pdf', 'documents/3/bail_bernard_lucie_2026.pdf',
    'c5f3e4a6b7d8c9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4', 'SIGNED_VALIDATED');

-- -----------------------------------------------------
-- Tokens (pour le document en attente de signature)
-- -----------------------------------------------------
INSERT INTO `tokens` (`document_id`, `locataire_id`, `token`, `expire_at`, `used`) VALUES
(1, 1,
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkb2N1bWVudF9pZCI6MSwibG9jYXRhaXJlX2lkIjoxfQ.token_test',
    DATE_ADD(NOW(), INTERVAL 48 HOUR),
    0);

-- -----------------------------------------------------
-- Audit log (quelques entrées d'exemple)
-- -----------------------------------------------------
INSERT INTO `audit_log` (`document_id`, `locataire_id`, `action`, `details`, `ip_address`) VALUES
(1, 1, 'DOCUMENT_UPLOADED', 'Document reçu depuis SOTHIS', '127.0.0.1'),
(1, 1, 'TOKEN_GENERATED', 'Lien de signature envoyé par mail', '127.0.0.1'),
(2, 2, 'DOCUMENT_OPENED', 'Locataire a ouvert le document', '90.56.123.45'),
(2, 2, 'DOCUMENT_SIGNED', 'Signature enregistrée, en attente validation SOTHIS', '90.56.123.45'),
(3, 3, 'DOCUMENT_VALIDATED', 'Document validé par SOTHIS', '127.0.0.1');
