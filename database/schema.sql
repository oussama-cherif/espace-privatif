-- Schéma de la base de données ESPACE-PRIVATIF
-- A exécuter une seule fois sur la base espace_privatif

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -----------------------------------------------------
-- clients : les sociétés clientes de Realsoft
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom`        VARCHAR(150) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `telephone`  VARCHAR(20),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- residences : les résidences gérées par un client
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `residences` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id`  INT UNSIGNED NOT NULL,
    `nom`        VARCHAR(150) NOT NULL,
    `adresse`    VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_residences_client`
        FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- locataires : les locataires rattachés à une résidence
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locataires` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `residence_id` INT UNSIGNED NOT NULL,
    `nom`          VARCHAR(100) NOT NULL,
    `prenom`       VARCHAR(100) NOT NULL,
    `email`        VARCHAR(255) NOT NULL,
    `telephone`    VARCHAR(20),
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_locataires_residence`
        FOREIGN KEY (`residence_id`) REFERENCES `residences`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- documents : les PDF envoyés par SOTHIS à signer
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `locataire_id` INT UNSIGNED NOT NULL,
    `nom_fichier` VARCHAR(255) NOT NULL,
    `chemin`      VARCHAR(500) NOT NULL,
    `hash_sha256` CHAR(64) NOT NULL,
    `status`      ENUM('PENDING_SIGNATURE','SIGNED_UNVALIDATED','SIGNED_VALIDATED') NOT NULL DEFAULT 'PENDING_SIGNATURE',
    `locked_by`   INT UNSIGNED DEFAULT NULL,
    `locked_at`   DATETIME DEFAULT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_documents_locataire`
        FOREIGN KEY (`locataire_id`) REFERENCES `locataires`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- tokens : liens de connexion envoyés par mail
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tokens` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT UNSIGNED NOT NULL,
    `locataire_id` INT UNSIGNED NOT NULL,
    `token`       VARCHAR(500) NOT NULL,
    `expire_at`   DATETIME NOT NULL,
    `used`        TINYINT(1) NOT NULL DEFAULT 0,
    `used_at`     DATETIME DEFAULT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tokens_document`
        FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_tokens_locataire`
        FOREIGN KEY (`locataire_id`) REFERENCES `locataires`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- signatures : données capturées au moment de la signature
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `signatures` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `document_id`      INT UNSIGNED NOT NULL,
    `locataire_id`     INT UNSIGNED NOT NULL,
    `hash_document`    CHAR(64) NOT NULL,
    `signature_image`  TEXT,
    `ip_address`       VARCHAR(45) NOT NULL,
    `user_agent`       VARCHAR(500),
    `signed_at`        DATETIME NOT NULL,
    `signature_crypto` TEXT,
    CONSTRAINT `fk_signatures_document`
        FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_signatures_locataire`
        FOREIGN KEY (`locataire_id`) REFERENCES `locataires`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- audit_log : journal de toutes les actions
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT UNSIGNED DEFAULT NULL,
    `locataire_id` INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `details`     TEXT,
    `ip_address`  VARCHAR(45),
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
