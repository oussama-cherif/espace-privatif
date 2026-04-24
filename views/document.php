<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature du document - Espace Privatif</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">

        <div class="document-header">
            <h1>Signature de document</h1>
            <p>
                <strong><?= htmlspecialchars($document['prenom'] . ' ' . $document['nom']) ?></strong>
                &mdash;
                <?= htmlspecialchars($document['residence_nom']) ?>
            </p>
            <p class="fichier"><?= htmlspecialchars($document['nom_fichier']) ?></p>
        </div>

        <div class="pdf-wrapper">
            <canvas id="pdf-canvas"></canvas>
            <div id="pdf-navigation">
                <button id="btn-prev">&#8592; Page précédente</button>
                <span id="page-info">Page <span id="page-num">1</span> / <span id="page-count">?</span></span>
                <button id="btn-next">Page suivante &#8594;</button>
            </div>
        </div>

        <div class="signature-section">
            <h2>Votre signature</h2>
            <p>Signez dans le cadre ci-dessous avec votre souris ou votre doigt.</p>
            <canvas id="signature-canvas"></canvas>
            <div class="signature-actions">
                <button id="btn-effacer" type="button">Effacer</button>
            </div>
        </div>

        <form id="form-signature" method="POST" action="/signer/soumettre">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="signature_data" id="signature_data">
            <input type="hidden" name="hash_document" value="<?= htmlspecialchars($document['hash_sha256']) ?>">
            <button type="submit" id="btn-signer" class="btn btn-primary">Signer le document</button>
        </form>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let pdfDoc    = null;
        let pageActuelle = 1;

        const pdfCanvas  = document.getElementById('pdf-canvas');
        const pdfCtx     = pdfCanvas.getContext('2d');

        function afficherPage(num) {
            pdfDoc.getPage(num).then(page => {
                const viewport = page.getViewport({ scale: 1.5 });
                pdfCanvas.width  = viewport.width;
                pdfCanvas.height = viewport.height;
                page.render({ canvasContext: pdfCtx, viewport });
                document.getElementById('page-num').textContent = num;
            });
        }

        pdfjsLib.getDocument('/document/pdf').promise.then(pdf => {
            pdfDoc = pdf;
            document.getElementById('page-count').textContent = pdf.numPages;
            afficherPage(pageActuelle);
        });

        document.getElementById('btn-prev').addEventListener('click', () => {
            if (pageActuelle > 1) { pageActuelle--; afficherPage(pageActuelle); }
        });
        document.getElementById('btn-next').addEventListener('click', () => {
            if (pageActuelle < pdfDoc.numPages) { pageActuelle++; afficherPage(pageActuelle); }
        });

        // --- Canvas de signature ---
        const sigCanvas = document.getElementById('signature-canvas');
        const sigCtx    = sigCanvas.getContext('2d');
        sigCanvas.width  = sigCanvas.offsetWidth || 600;
        sigCanvas.height = 180;

        let dessine = false;

        function getPos(e) {
            const rect = sigCanvas.getBoundingClientRect();
            const src  = e.touches ? e.touches[0] : e;
            return { x: src.clientX - rect.left, y: src.clientY - rect.top };
        }

        sigCanvas.addEventListener('mousedown',  e => { dessine = true; sigCtx.beginPath(); const p = getPos(e); sigCtx.moveTo(p.x, p.y); });
        sigCanvas.addEventListener('mousemove',  e => { if (!dessine) return; const p = getPos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); });
        sigCanvas.addEventListener('mouseup',    () => { dessine = false; });
        sigCanvas.addEventListener('mouseleave', () => { dessine = false; });

        sigCanvas.addEventListener('touchstart',  e => { e.preventDefault(); dessine = true; sigCtx.beginPath(); const p = getPos(e); sigCtx.moveTo(p.x, p.y); });
        sigCanvas.addEventListener('touchmove',   e => { e.preventDefault(); if (!dessine) return; const p = getPos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); });
        sigCanvas.addEventListener('touchend',    () => { dessine = false; });

        sigCtx.strokeStyle = '#000';
        sigCtx.lineWidth   = 2;
        sigCtx.lineCap     = 'round';

        document.getElementById('btn-effacer').addEventListener('click', () => {
            sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        });

        document.getElementById('form-signature').addEventListener('submit', e => {
            const pixels  = sigCtx.getImageData(0, 0, sigCanvas.width, sigCanvas.height).data;
            const estVide = !Array.from(pixels).some((v, i) => i % 4 === 3 && v > 0);
            if (estVide) {
                e.preventDefault();
                alert('Veuillez apposer votre signature avant de valider.');
                return;
            }
            document.getElementById('signature_data').value = sigCanvas.toDataURL('image/png');
        });
    </script>
</body>
</html>
