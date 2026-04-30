<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/mail.php';

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $config['host'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $config['username'];
        $this->mailer->Password   = $config['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = $config['port'];
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->Timeout    = 5;
        $this->mailer->setFrom($config['from'], $config['from_name']);
    }

    public function confirmerLocataire(string $email, string $prenom, string $nomFichier, string $dateSignature): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email, $prenom);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Confirmation de signature - Espace Privatif';
        $this->mailer->Body    = $this->templateConfirmation($prenom, $nomFichier, $dateSignature);
        $this->mailer->AltBody = strip_tags($this->templateConfirmation($prenom, $nomFichier, $dateSignature));
        $this->mailer->send();
    }

    public function notifierGestionnaire(string $email, string $nomLocataire, string $nomFichier, string $dateSignature, string $residence): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Document signé - ' . $nomFichier;
        $this->mailer->Body    = $this->templateNotification($nomLocataire, $nomFichier, $dateSignature, $residence);
        $this->mailer->AltBody = strip_tags($this->templateNotification($nomLocataire, $nomFichier, $dateSignature, $residence));
        $this->mailer->send();
    }

    private function templateConfirmation(string $prenom, string $nomFichier, string $date): string
    {
        return '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
            <h2 style="color:#2563eb">Votre signature a été enregistrée</h2>
            <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
            <p>Votre signature électronique sur le document <strong>' . htmlspecialchars($nomFichier) . '</strong> a bien été enregistrée le ' . htmlspecialchars($date) . '.</p>
            <p>Le document est en cours de validation. Vous recevrez une confirmation une fois le processus terminé.</p>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
            <p style="color:#6b7280;font-size:12px">Ce mail est envoyé automatiquement par Espace Privatif. Merci de ne pas y répondre.</p>
        </div>';
    }

    private function templateNotification(string $nomLocataire, string $nomFichier, string $date, string $residence): string
    {
        return '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
            <h2 style="color:#2563eb">Document signé</h2>
            <p>Le document <strong>' . htmlspecialchars($nomFichier) . '</strong> a été signé par <strong>' . htmlspecialchars($nomLocataire) . '</strong> le ' . htmlspecialchars($date) . '.</p>
            <p>Résidence : ' . htmlspecialchars($residence) . '</p>
            <p>Le document est transmis à SOTHIS pour validation finale.</p>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
            <p style="color:#6b7280;font-size:12px">Ce mail est envoyé automatiquement par Espace Privatif.</p>
        </div>';
    }
}
