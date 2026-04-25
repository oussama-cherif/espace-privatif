<?php

require_once __DIR__ . '/../core/Database.php';

class DocumentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, l.nom, l.prenom, l.email,
                    r.nom AS residence_nom, r.adresse AS residence_adresse,
                    c.email AS gestionnaire_email
             FROM documents d
             JOIN locataires l ON l.id = d.locataire_id
             JOIN residences r ON r.id = l.residence_id
             JOIN clients c ON c.id = r.client_id
             WHERE d.id = :id'
        );
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE documents SET status = :status WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
