<?php

require_once __DIR__ . '/../core/Database.php';

class TokenModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findValidToken(string $tokenString): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, d.status AS document_status
             FROM tokens t
             JOIN documents d ON d.id = t.document_id
             WHERE t.token = :token
               AND t.used = 0
               AND t.expire_at > NOW()'
        );
        $stmt->execute(['token' => $tokenString]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function markAsUsed(int $tokenId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tokens SET used = 1, used_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $tokenId]);
    }
}