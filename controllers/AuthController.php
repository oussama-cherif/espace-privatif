<?php

require_once __DIR__ . '/../models/TokenModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class AuthController
{
    public function signer(): void
    {
        $tokenString = $_GET['token'] ?? '';

        if (empty($tokenString)) {
            $this->renderError('Lien invalide. Aucun token fourni.');
            return;
        }

        $config     = require __DIR__ . '/../config/app.php';
        $tokenModel = new TokenModel();

        try {
            $payload = JWT::decode($tokenString, new Key($config['jwt_secret'], $config['jwt_algo']));
        } catch (ExpiredException $e) {
            $this->renderError('Ce lien a expiré. Contactez votre gestionnaire.');
            return;
        } catch (SignatureInvalidException $e) {
            $this->renderError('Lien invalide ou falsifié.');
            return;
        } catch (Exception $e) {
            $this->renderError('Lien invalide.');
            return;
        }

        $tokenRow = $tokenModel->findValidToken($tokenString);

        if ($tokenRow === null) {
            $this->renderError('Ce lien a déjà été utilisé ou a expiré.');
            return;
        }

        if ($tokenRow['document_status'] !== 'PENDING_SIGNATURE') {
            $this->renderError('Ce document a déjà été signé.');
            return;
        }

        session_start();
        $_SESSION['locataire_id'] = $tokenRow['locataire_id'];
        $_SESSION['document_id']  = $tokenRow['document_id'];
        $_SESSION['token_id']     = $tokenRow['id'];
        $_SESSION['expire_at']    = time() + (int) $config['session_lifetime'];

        header('Location: /document');
        exit;
    }

    private function renderError(string $message): void
    {
        require __DIR__ . '/../views/error.php';
        exit;
    }
}