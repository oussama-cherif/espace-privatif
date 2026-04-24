<?php

/**
 * Mock SOTHIS — simulation de l'envoi d'un document à ESPACE-PRIVATIF
 *
 * Usage : php mock/upload.php
 *
 * Simule ce que SOTHIS ferait en production :
 * 1. Générer un PDF (bail ou état des lieux)
 * 2. Calculer son hash SHA-256
 * 3. L'enregistrer dans storage/documents/
 * 4. Créer l'entrée en base de données
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';

$locataireId = 1;
$nomFichier  = 'bail_martin_sophie_2026.pdf';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('SOTHIS');
$pdf->SetAuthor('Realsoft');
$pdf->SetTitle('Contrat de location');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'CONTRAT DE LOCATION', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 11);

$contenu = <<<HTML
<p><strong>Résidence :</strong> Les Calanques — 12 avenue de la Mer, 13009 Marseille</p>
<p><strong>Locataire :</strong> Sophie Martin</p>
<p><strong>Période :</strong> du 01/06/2026 au 31/08/2026</p>
<p><strong>Loyer mensuel :</strong> 850 € charges comprises</p>
<br/>
<p>Le présent contrat est établi entre le gestionnaire de la résidence et le locataire désigné ci-dessus,
conformément aux dispositions de la loi du 6 juillet 1989 tendant à améliorer les rapports locatifs.</p>
<br/>
<p>Le locataire reconnaît avoir pris connaissance du règlement intérieur de la résidence et s'engage
à le respecter pendant toute la durée de son séjour.</p>
<br/>
<p>Ce document est en attente de signature électronique.</p>
HTML;

$pdf->writeHTML($contenu, true, false, true, false, '');

$storageDir = __DIR__ . '/../storage/documents/' . $locataireId;
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$cheminFichier = $storageDir . '/' . $nomFichier;
$pdf->Output($cheminFichier, 'F');

$hashSha256 = hash_file('sha256', $cheminFichier);
$cheminRelatif = 'documents/' . $locataireId . '/' . $nomFichier;

$db   = Database::getConnection();
$stmt = $db->prepare(
    'INSERT INTO documents (locataire_id, nom_fichier, chemin, hash_sha256, status)
     VALUES (:locataire_id, :nom_fichier, :chemin, :hash_sha256, :status)'
);
$stmt->execute([
    'locataire_id' => $locataireId,
    'nom_fichier'  => $nomFichier,
    'chemin'       => $cheminRelatif,
    'hash_sha256'  => $hashSha256,
    'status'       => 'PENDING_SIGNATURE',
]);

$documentId = (int) $db->lastInsertId();

$logStmt = $db->prepare(
    'INSERT INTO audit_log (document_id, locataire_id, action, details, ip_address)
     VALUES (:document_id, :locataire_id, :action, :details, :ip)'
);
$logStmt->execute([
    'document_id'  => $documentId,
    'locataire_id' => $locataireId,
    'action'       => 'DOCUMENT_UPLOADED',
    'details'      => 'Document généré et reçu depuis SOTHIS (mock)',
    'ip'           => '127.0.0.1',
]);

echo "Document créé avec succès." . PHP_EOL;
echo "ID         : " . $documentId . PHP_EOL;
echo "Fichier    : " . $cheminFichier . PHP_EOL;
echo "Hash SHA256: " . $hashSha256 . PHP_EOL;
echo PHP_EOL;
echo "Prochaine étape : php mock/generate_token.php " . $documentId . PHP_EOL;
