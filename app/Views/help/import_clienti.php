<?php
/**
 * Guida della sotto-sezione Impostazioni → Import Clienti.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    L'<strong>import clienti</strong> serve a portare dentro il gestionale l'anagrafica storica
    che vive nel software di contabilità. I dati non entrano subito in anagrafica: si fermano in
    un <strong>parcheggio</strong>, da cui si crea il cliente vero solo quando serve davvero.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-question-circle me-1"></i>Perché un parcheggio</h6>
<p>
    L'archivio storico contiene migliaia di nominativi, la gran parte dei quali non lavora più con
    l'azienda da anni. Riversarli tutti in anagrafica renderebbe lente e confuse tutte le ricerche
    e tutti i menu a tendina del gestionale. Nel parcheggio invece non danno fastidio a nessuno:
    restano lì, consultabili, finché non servono.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-upload me-1"></i>1. Caricare l'export</h6>
<p>
    Serve un file <strong>CSV</strong>, con qualsiasi struttura di colonne. Separatore
    <code>;</code> oppure <code>,</code>, riconosciuto da solo. Nel passo successivo si dice al
    sistema quale colonna del file corrisponde a quale campo.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrow-left-right me-1"></i>2. Mappare le colonne</h6>
<p>
    Per ogni colonna del file si sceglie il campo di destinazione; quelle che non servono si
    lasciano su <em>Non importare</em>. Un campo già assegnato sparisce dalle altre tendine, così
    non si può usarlo due volte.
</p>
<p>
    Il campo <strong>Codice</strong> è obbligatorio: identifica il record e permette di
    riconoscerlo ai caricamenti successivi. La mappatura viene ricordata, quindi dalla seconda
    volta in poi arriva già compilata.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrow-repeat me-1"></i>Ricaricare lo stesso export</h6>
<div class="alert alert-info py-2">
    <i class="bi bi-info-circle me-1"></i>
    Si può fare tutte le volte che si vuole. Le righe con un codice già presente vengono
    <strong>aggiornate</strong>, non duplicate, e i clienti già creati non vengono toccati in
    nessun modo. Utile per correggere ed esportare di nuovo senza fare danni.
</div>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-person-plus me-1"></i>3. Creare il cliente</h6>
<p>
    Dall'elenco <strong>Clienti da migrare</strong> si cerca il nominativo (la ricerca guarda in
    tutte le colonne: denominazione, città, partita IVA…) e si preme <strong>Crea cliente</strong>.
    Si apre il normale form di inserimento, già compilato con i dati storici.
</p>
<p>
    <strong>I dati restano tutti modificabili</strong>: l'archivio storico contiene imprecisioni,
    e questo è il momento per sistemarle. Il cliente viene creato con quello che si vede a
    schermo, non con i dati originali. Conviene anche premere il pulsante della geocodifica per
    ricavare le coordinate dall'indirizzo.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-graph-up-arrow me-1"></i>Avanzamento</h6>
<p class="mb-0">
    La pagina principale mostra quanti nominativi sono in parcheggio, quanti sono già stati
    trasformati in clienti e quanti restano. Un nominativo già usato sparisce dall'elenco e non
    viene più riproposto.
</p>
