<?php

require_once __DIR__ . '/../models/DocumentModel.php';
require_once __DIR__ . '/../models/TokenModel.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/PdfSigner.php';
require_once __DIR__ . '/../core/SothisNotifier.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../vendor/autoload.php';

class SignatureController
{
    public function soumettre(): void
    {
        session_start();

        if (empty($_SESSION['document_id']) || empty($_SESSION['locataire_id'])) {
            http_response_code(403);
            $message = 'Session expirée. Veuillez utiliser le lien reçu par mail.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $message = 'Requête invalide.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $signatureData = $_POST['signature_data'] ?? '';
        $hashRecu      = $_POST['hash_document']  ?? '';

        if (empty($signatureData) || !str_starts_with($signatureData, 'data:image/png;base64,')) {
            http_response_code(400);
            $message = 'Signature manquante ou invalide.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $documentModel = new DocumentModel();
        $document      = $documentModel->findById((int) $_SESSION['document_id']);

        if ($document === null || $document['status'] !== 'PENDING_SIGNATURE') {
            http_response_code(409);
            $message = 'Ce document ne peut plus être signé.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        if (!hash_equals($document['hash_sha256'], $hashRecu)) {
            http_response_code(409);
            $message = 'Le document a été modifié. La signature est impossible.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $imageData    = base64_decode(str_replace('data:image/png;base64,', '', $signatureData));
        $signatureDir = __DIR__ . '/../storage/signatures/' . $document['id'];

        if (!is_dir($signatureDir)) {
            mkdir($signatureDir, 0755, true);
        }

        $signaturePath = $signatureDir . '/signature.png';
        file_put_contents($signaturePath, $imageData);

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $lock = $db->prepare('SELECT id, status FROM documents WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $document['id']]);
            $row = $lock->fetch();

            if ($row === false || $row['status'] !== 'PENDING_SIGNATURE') {
                $db->rollBack();
                http_response_code(409);
                $message = 'Ce document est déjà en cours de signature ou a déjà été signé.';
                require __DIR__ . '/../views/error.php';
                return;
            }

            $stmt = $db->prepare(
                'INSERT INTO signatures
                    (document_id, locataire_id, hash_document, signature_image, ip_address, user_agent, signed_at)
                 VALUES
                    (:document_id, :locataire_id, :hash_document, :signature_image, :ip, :ua, NOW())'
            );
            $stmt->execute([
                'document_id'     => $document['id'],
                'locataire_id'    => $_SESSION['locataire_id'],
                'hash_document'   => $document['hash_sha256'],
                'signature_image' => 'signatures/' . $document['id'] . '/signature.png',
                'ip'              => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua'              => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);

            $db->prepare('UPDATE documents SET status = :status WHERE id = :id')
               ->execute(['status' => 'SIGNED_UNVALIDATED', 'id' => $document['id']]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            $message = 'Une erreur est survenue lors de l\'enregistrement de la signature.';
            require __DIR__ . '/../views/error.php';
            return;
        }

        $cheminOriginal    = __DIR__ . '/../storage/' . $document['chemin'];
        $cheminSignature   = $signaturePath;
        $cheminSigne       = __DIR__ . '/../storage/documents/' . $document['id'] . '/signed/' . $document['nom_fichier'];

        $pdfSigner = new PdfSigner();
        $pdfSigner->genererPdfSigne($cheminOriginal, $cheminSignature, $cheminSigne, [
            'prenom'         => $document['prenom'],
            'nom'            => $document['nom'],
            'email'          => $document['email'],
            'date_signature' => date('d/m/Y H:i:s'),
            'ip'             => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'hash_document'  => $document['hash_sha256'],
            'residence'      => $document['residence_nom'],
            'nom_fichier'    => $document['nom_fichier'],
        ]);

        $tokenModel = new TokenModel();
        $tokenModel->markAsUsed((int) $_SESSION['token_id']);

        $logStmt = $db->prepare(
            'INSERT INTO audit_log (document_id, locataire_id, action, details, ip_address)
             VALUES (:document_id, :locataire_id, :action, :details, :ip)'
        );
        $logStmt->execute([
            'document_id'  => $document['id'],
            'locataire_id' => $_SESSION['locataire_id'],
            'action'       => 'DOCUMENT_SIGNED',
            'details'      => 'Signature enregistrée — en attente de validation SOTHIS',
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $notifier = new SothisNotifier();
        $notifier->notifierSignature($document['id'], [
            'locataire'  => $document['prenom'] . ' ' . $document['nom'],
            'email'      => $document['email'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'hash'       => $document['hash_sha256'],
            'signed_at'  => date('Y-m-d H:i:s'),
        ]);

        $dateSignature = date('d/m/Y à H:i');
        try {
            $mailer = new Mailer();
            $mailer->confirmerLocataire(
                $document['email'],
                $document['prenom'],
                $document['nom_fichier'],
                $dateSignature
            );
            $mailer->notifierGestionnaire(
                $document['gestionnaire_email'],
                $document['prenom'] . ' ' . $document['nom'],
                $document['nom_fichier'],
                $dateSignature,
                $document['residence_nom']
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi mail : ' . $e->getMessage());
        }

        $documentId = $document['id'];
        session_destroy();

        header('Location: /document/confirmation?doc=' . $documentId);
        exit;
    }
}
