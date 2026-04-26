<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature enregistrée - Espace Privatif</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Signature enregistrée</h1>
        <p>Votre signature a bien été enregistrée.</p>
        <div id="statut-box">
            <p id="statut-message">Le document est en cours de validation par SOTHIS...</p>
            <div id="loader"></div>
        </div>
    </div>

    <style>
        #statut-box { margin-top: 1.5rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; border-left: 4px solid #2563eb; }
        #loader { width: 32px; height: 32px; border: 3px solid #ddd; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite; margin-top: 0.75rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .valide { background: #f0fdf4 !important; border-left-color: #16a34a !important; }
    </style>

    <script>
        const documentId = <?= (int) ($documentId ?? 0) ?>;

        if (documentId && typeof WebSocket !== 'undefined') {
            let updateEnAttente = null;
            let pret = false;

            function afficherValidation() {
                document.getElementById('statut-message').textContent = 'Document validé par SOTHIS. Votre exemplaire est disponible au téléchargement.';
                document.getElementById('loader').style.display = 'none';
                document.getElementById('statut-box').classList.add('valide');
                const btn = document.createElement('a');
                btn.href = '/document/telecharger?doc=' + documentId;
                btn.textContent = 'Télécharger le document signé';
                btn.className = 'btn';
                btn.style.marginTop = '0.5rem';
                btn.style.display = 'inline-block';
                document.getElementById('statut-box').appendChild(btn);
            }

            setTimeout(() => {
                pret = true;
                if (updateEnAttente) {
                    afficherValidation();
                    updateEnAttente.ws.close();
                }
            }, 2000);

            const ws = new WebSocket('ws://localhost:8080');

            ws.onopen = () => {
                ws.send(JSON.stringify({ type: 'subscribe', document_id: documentId }));
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'status_update' && data.status === 'SIGNED_VALIDATED') {
                    if (pret) {
                        afficherValidation();
                        ws.close();
                    } else {
                        updateEnAttente = { ws };
                    }
                }
            };

            ws.onerror = () => {
                document.getElementById('statut-message').textContent = 'Votre signature a été enregistrée. Vous recevrez un mail de confirmation.';
                document.getElementById('loader').style.display = 'none';
            };
        }
    </script>
</body>
</html>
