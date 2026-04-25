<?php

require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfSigner
{
    public function genererPdfSigne(
        string $cheminOriginal,
        string $cheminSignature,
        string $cheminDestination,
        array  $metadonnees
    ): void {
        $pdf = new Fpdi();
        $pdf->SetCreator('Espace-Privatif');
        $pdf->SetAuthor('Espace-Privatif');
        $pdf->SetTitle('Document signé');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $nbPages = $pdf->setSourceFile($cheminOriginal);

        for ($i = 1; $i <= $nbPages; $i++) {
            $tplId    = $pdf->importPage($i);
            $taille   = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($taille['orientation'], [$taille['width'], $taille['height']]);
            $pdf->useTemplate($tplId);

            if ($i === $nbPages) {
                $this->ajouterSignatureVisuelle($pdf, $cheminSignature, $taille, $metadonnees);
            }
        }

        $this->ajouterPageCertificat($pdf, $metadonnees);

        $dossier = dirname($cheminDestination);
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        $pdf->Output($cheminDestination, 'F');
    }

    private function ajouterSignatureVisuelle(Fpdi $pdf, string $cheminSignature, array $taille, array $meta): void
    {
        $margeX = 15;
        $margeY = $taille['height'] - 60;

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY($margeX, $margeY);
        $pdf->Cell(0, 5, 'Signé électroniquement par ' . $meta['prenom'] . ' ' . $meta['nom'], 0, 1);
        $pdf->SetX($margeX);
        $pdf->Cell(0, 5, 'Le ' . $meta['date_signature'] . ' — IP : ' . $meta['ip'], 0, 1);

        if (file_exists($cheminSignature)) {
            $pdf->Image($cheminSignature, $margeX, $margeY + 12, 60, 20, 'PNG');
        }

        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line($margeX, $margeY - 3, $taille['width'] - $margeX, $margeY - 3);
    }

    private function ajouterPageCertificat(Fpdi $pdf, array $meta): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Certificat de signature électronique', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->RoundedRect(15, $pdf->GetY(), 180, 100, 3, '1111', 'DF');

        $pdf->SetX(20);
        $pdf->SetY($pdf->GetY() + 5);

        $lignes = [
            ['Signataire',        $meta['prenom'] . ' ' . $meta['nom']],
            ['Email',             $meta['email']],
            ['Date de signature', $meta['date_signature']],
            ['Adresse IP',        $meta['ip']],
            ['Navigateur',        mb_substr($meta['user_agent'], 0, 80)],
            ['Hash SHA-256',      $meta['hash_document']],
            ['Résidence',         $meta['residence']],
            ['Document',          $meta['nom_fichier']],
        ];

        foreach ($lignes as [$label, $valeur]) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetX(20);
            $pdf->Cell(45, 8, $label . ' :', 0, 0);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->MultiCell(140, 8, $valeur, 0, 'L');
        }

        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(15);
        $pdf->MultiCell(180, 5,
            'Ce document a été signé électroniquement conformément au règlement eIDAS (UE n°910/2014). ' .
            'Le hash SHA-256 ci-dessus permet de vérifier l\'intégrité du document original. ' .
            'Les métadonnées de signature sont conservées dans le journal d\'audit d\'Espace-Privatif.',
            0, 'L'
        );
    }
}
