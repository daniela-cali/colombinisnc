/**
 * Zip delle proposte in PDF, composto nel browser.
 *
 * La conversione in PDF costa circa 0,6 secondi a proposta: tutte in una richiesta
 * supererebbero presto i 60 secondi dopo cui Nginx la interrompe. Lo script le chiede quindi a
 * lotti, una richiesta dopo l'altra, mostra l'avanzamento e alla fine mette i PDF in uno zip
 * con fflate. Sul server non resta niente.
 *
 * Usa la finestra #modal-zip-pdf della view. Richiede fflate (window.fflate).
 *
 * opzioni: { url, lotto, csrf, nome }
 *   url   endpoint del lotto, risponde { ok, file: [{ nome, pdf (base64) }], scartate: [...] }
 *   lotto quante proposte per richiesta
 *   csrf  token CSRF, mandato nell'intestazione X-CSRF-TOKEN
 *   nome  nome del file zip
 */
async function scaricaZipProposte(ids, opzioni) {
    var modalEl = document.getElementById('modal-zip-pdf');
    var stato   = document.getElementById('zip-pdf-stato');
    var barra   = document.getElementById('zip-pdf-barra');
    var errore  = document.getElementById('zip-pdf-errore');
    var footer  = document.getElementById('zip-pdf-footer');
    var modal   = bootstrap.Modal.getOrCreateInstance(modalEl);

    function avanzamento(fatte) {
        stato.textContent = 'Conversione: ' + fatte + ' di ' + ids.length;
        barra.style.width = Math.round(fatte / ids.length * 100) + '%';
    }

    function fallisci(messaggio) {
        stato.textContent = 'Lo zip non è stato creato.';
        errore.textContent = messaggio;
        errore.classList.remove('d-none');
        footer.classList.remove('d-none');
    }

    errore.classList.add('d-none');
    footer.classList.add('d-none');
    avanzamento(0);
    modal.show();

    var file     = {};  // nome nello zip → contenuto
    var scartate = [];

    for (var inizio = 0; inizio < ids.length; inizio += opzioni.lotto) {
        var parametri = new URLSearchParams();
        ids.slice(inizio, inizio + opzioni.lotto).forEach(function (id) { parametri.append('ids[]', id); });

        var risposta;
        try {
            var r = await fetch(opzioni.url, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': opzioni.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body:    parametri
            });
            risposta = await r.json();
        } catch (e) {
            fallisci('Il server non ha risposto. Riprova; se si ripete, scarica i Word.');
            return;
        }

        if (! risposta.ok) {
            fallisci(risposta.motivo);
            return;
        }

        risposta.file.forEach(function (f) { file[nomeLibero(f.nome, file)] = base64InByte(f.pdf); });
        scartate = scartate.concat(risposta.scartate);
        avanzamento(Math.min(inizio + opzioni.lotto, ids.length));
    }

    if (Object.keys(file).length === 0) {
        fallisci('Nessuna proposta generata. ' + scartate.join(' '));
        return;
    }
    if (scartate.length) {
        file['NON GENERATE.txt'] = fflate.strToU8(scartate.join('\r\n') + '\r\n');
    }

    // level 0: i PDF sono già compressi, comprimerli di nuovo costa tempo e non rende.
    var zip = fflate.zipSync(file, { level: 0 });
    var url = URL.createObjectURL(new Blob([zip], { type: 'application/zip' }));
    var a   = document.createElement('a');
    a.href     = url;
    a.download = opzioni.nome;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 10000);

    modal.hide();
}

/* Il nome con « (2)», « (3)»… se nello zip c'è già: il server rende unici i nomi di un lotto,
   ma non vede i lotti precedenti. Stesso criterio di AbbonamentiController::nomeLibero(). */
function nomeLibero(nome, usati) {
    var chiavi = Object.keys(usati).map(function (k) { return k.toLowerCase(); });
    var base   = nome.replace(/\.pdf$/i, '');
    var libero = nome;

    for (var n = 2; chiavi.indexOf(libero.toLowerCase()) !== -1; n++) {
        libero = base + ' (' + n + ').pdf';
    }
    return libero;
}

function base64InByte(base64) {
    var binario = atob(base64);
    var byte    = new Uint8Array(binario.length);
    for (var i = 0; i < binario.length; i++) byte[i] = binario.charCodeAt(i);
    return byte;
}
