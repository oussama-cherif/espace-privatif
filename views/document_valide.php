<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document signé et validé - Espace Privatif</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Document signé et validé</h1>
        <p>Bonjour <?= htmlspecialchars($document['prenom'] . ' ' . $document['nom']) ?>,</p>
        <p>Votre document <strong><?= htmlspecialchars($document['nom_fichier']) ?></strong> a été signé et validé.</p>
        <a href="/document/pdf" class="btn">Télécharger le document signé</a>
    </div>
</body>
</html>
