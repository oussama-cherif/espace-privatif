<?php

require_once __DIR__ . '/../models/DocumentModel.php';

class DocumentController
{
    public function afficher(): void
    {
        session_start();

        if (empty($_SESSION['document_id']) || empty($_SESSION['locataire_id'])) {
            http_response_code(403);
            $message = 'Session expirée. Veuillez utiliser le lien reçu par mail.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        if (time() > $_SESSION['expire_at']) {
            session_destroy();
            http_response_code(403);
            $message = 'Votre session a expiré. Veuillez utiliser le lien reçu par mail.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $documentModel = new DocumentModel();
        $document      = $documentModel->findById((int) $_SESSION['document_id']);

        if ($document === null) {
            http_response_code(404);
            require __DIR__ . '/../views/404.php';
            return;
        }

        match ($document['status']) {
            'PENDING_SIGNATURE'  => require __DIR__ . '/../views/document.php',
            'SIGNED_UNVALIDATED' => require __DIR__ . '/../views/document_en_attente.php',
            'SIGNED_VALIDATED'   => require __DIR__ . '/../views/document_valide.php',
        };
    }

    public function telecharger(): void
    {
        $documentId    = (int) ($_GET['doc'] ?? 0);
        $documentModel = new DocumentModel();
        $document      = $documentModel->findById($documentId);

        if ($document === null || $document['status'] !== 'SIGNED_VALIDATED') {
            http_response_code(403);
            $message = 'Ce document n\'est pas disponible au téléchargement.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $chemin = __DIR__ . '/../storage/documents/' . $document['id'] . '/signed/' . $document['nom_fichier'];

        if (!file_exists($chemin)) {
            http_response_code(404);
            require __DIR__ . '/../views/404.php';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($chemin) . '"');
        header('Content-Length: ' . filesize($chemin));
        readfile($chemin);
        exit;
    }

    public function servir(): void
    {
        session_start();

        if (empty($_SESSION['document_id'])) {
            http_response_code(403);
            exit;
        }

        $documentModel = new DocumentModel();
        $document      = $documentModel->findById((int) $_SESSION['document_id']);

        if ($document === null) {
            http_response_code(404);
            exit;
        }

        $chemin = __DIR__ . '/../storage/' . $document['chemin'];

        if (!file_exists($chemin)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($chemin) . '"');
        header('Content-Length: ' . filesize($chemin));
        readfile($chemin);
        exit;
    }
}
