/**
 * Invio per email delle proposte di abbonamento: guida la finestra del partial
 * abbonamenti/_invio_email.php, uguale per una proposta e per venti.
 *
 * 1. chiede al server la preparazione: oggetto, testo, firma e le righe con il loro stato;
 * 2. l'operatore corregge oggetto e testo se vuole, poi Invia;
 * 3. spedisce una proposta per richiesta, in fila, con una pausa fra un invio e l'altro: ogni
 *    richiesta resta breve, e il ritmo lento protegge la casella Aruba dell'operatore;
 * 4. a fine invio il riepilogo, e Chiudi ricarica la pagina per mostrare le proposte inviate.
 *
 * Nell'invio multiplo le righe senza email o già inviate si saltano. Nell'invio singolo una
 * proposta già inviata si rimanda dopo una conferma.
 *
 * opzioni: { urlPrepara, urlInvia, pausa }
 *   urlInvia contiene {id}, sostituito con l'id dell'abbonamento
 *   pausa    millisecondi fra un invio e l'altro
 */
function apriInvioProposte(ids, opzioni) {
    var el = function (id) { return document.getElementById(id); };
    var modal      = bootstrap.Modal.getOrCreateInstance(el('modal-invio-email'));
    var csrf       = el('form-invio-email').querySelector('input[type="hidden"]').value;
    var caricando  = el('invio-caricamento');
    var errore     = el('invio-errore');
    var contenuto  = el('invio-contenuto');
    var oggetto    = el('invio-oggetto');
    var testo      = el('invio-testo');
    var righeEl    = el('invio-righe');
    var avanz      = el('invio-avanzamento');
    var barra      = el('invio-barra');
    var riepilogo  = el('invio-riepilogo');
    var btnInvia   = el('invio-invia');
    var btnAnnulla = el('invio-annulla');
    var btnChiudi  = el('invio-chiudi');

    var singolo = ids.length === 1;
    var dati    = null;

    // Stato iniziale: la finestra si riapre pulita a ogni uso.
    caricando.classList.remove('d-none');
    [errore, contenuto, avanz, riepilogo, btnChiudi].forEach(function (x) { x.classList.add('d-none'); });
    btnAnnulla.classList.remove('d-none');
    btnInvia.classList.remove('d-none');
    btnInvia.disabled = true;
    oggetto.disabled = testo.disabled = false;
    modal.show();

    function richiesta(url, campi) {
        var corpo = new URLSearchParams();
        Object.keys(campi).forEach(function (k) {
            [].concat(campi[k]).forEach(function (v) { corpo.append(k, v); });
        });
        return fetch(url, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body:    corpo
        }).then(function (r) { return r.json(); });
    }

    function mostraErrore(messaggio) {
        caricando.classList.add('d-none');
        errore.textContent = messaggio;
        errore.classList.remove('d-none');
        btnInvia.classList.add('d-none');
    }

    function statoRiga(riga, testoStato, classe) {
        var cella = riga.el.querySelector('.invio-stato');
        cella.textContent = testoStato;
        cella.className   = 'invio-stato ' + (classe || 'text-muted');
    }

    // Le righe da spedire: nell'invio multiplo senza quelle senza email o già inviate.
    function daInviare() {
        return dati.proposte.filter(function (p) {
            return p.email && (singolo || ! p.inviata);
        });
    }

    richiesta(opzioni.urlPrepara, { 'ids[]': ids }).then(function (r) {
        if (! r.ok) {
            mostraErrore(r.motivo);
            return;
        }
        dati = r;
        oggetto.value = r.oggetto;
        testo.value   = r.testo;
        el('invio-firma').srcdoc = r.firma || '<p style="font-family:sans-serif;color:#888">Nessuna firma: caricala nel tuo profilo.</p>';

        righeEl.innerHTML = '';
        r.proposte.forEach(function (p) {
            var tr = document.createElement('tr');
            ['cliente', 'email'].forEach(function (k) {
                var td = document.createElement('td');
                td.textContent = p[k] || '—';
                tr.appendChild(td);
            });
            var td = document.createElement('td');
            td.className = 'invio-stato';
            tr.appendChild(td);
            righeEl.appendChild(tr);
            p.el = tr;

            if (! p.email) {
                statoRiga(p, 'Senza email: da spedire per posta', 'text-warning');
            } else if (p.inviata) {
                statoRiga(p, singolo ? 'Già inviata il ' + p.inviata : 'Già inviata il ' + p.inviata + ': si salta');
            } else {
                statoRiga(p, 'Da inviare');
            }
        });

        caricando.classList.add('d-none');
        contenuto.classList.remove('d-none');
        btnInvia.disabled = daInviare().length === 0;
    }).catch(function () {
        mostraErrore('Il server non ha risposto. Riprova tra poco.');
    });

    btnInvia.onclick = async function () {
        var coda = daInviare();
        if (coda.length === 0) return;

        var reinvio = '0';
        if (singolo && coda[0].inviata) {
            if (! confirm('Già inviata il ' + coda[0].inviata + '. Inviarla di nuovo?')) return;
            reinvio = '1';
        }
        if (oggetto.value.trim() === '' || testo.value.trim() === '') {
            alert('Oggetto e testo sono obbligatori.');
            return;
        }

        oggetto.disabled = testo.disabled = true;
        btnInvia.classList.add('d-none');
        btnAnnulla.classList.add('d-none');
        avanz.classList.remove('d-none');
        barra.style.width = '0%';

        var inviate = 0, nonInviate = [], perPosta = dati.proposte.filter(function (p) { return ! p.email; }).length;

        for (var i = 0; i < coda.length; i++) {
            var p = coda[i];
            statoRiga(p, 'Invio in corso…', 'text-primary');
            try {
                var r = await richiesta(opzioni.urlInvia.replace('{id}', p.id), {
                    oggetto: oggetto.value, testo: testo.value, anno: dati.anno, reinvio: reinvio
                });
                if (r.ok) {
                    inviate++;
                    statoRiga(p, 'Inviata', 'text-success fw-semibold');
                } else {
                    r.per_posta ? perPosta++ : nonInviate.push(p);
                    statoRiga(p, r.motivo, r.per_posta ? 'text-warning' : 'text-danger');
                }
            } catch (e) {
                nonInviate.push(p);
                statoRiga(p, 'Il server non ha risposto: non inviata.', 'text-danger');
            }
            barra.style.width = Math.round((i + 1) / coda.length * 100) + '%';

            if (i < coda.length - 1) {
                await new Promise(function (ok) { setTimeout(ok, opzioni.pausa); });
            }
        }

        var parti = [inviate + (inviate === 1 ? ' inviata' : ' inviate')];
        if (nonInviate.length) parti.push(nonInviate.length + ' non inviate (motivo accanto a ciascuna)');
        if (perPosta) parti.push(perPosta + ' da spedire per posta');
        riepilogo.textContent = parti.join(' · ');
        riepilogo.classList.remove('d-none');
        btnChiudi.classList.remove('d-none');
    };

    btnChiudi.onclick = function () { location.reload(); };
}
