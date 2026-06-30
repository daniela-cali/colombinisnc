<?php
/**
 * Guida della sezione Cantieri.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 * Solo contenuto HTML/Bootstrap: nessun extend, nessuna variabile dal controller.
 */
?>
<p class="text-muted">
    Un <strong>cantiere</strong> raggruppa il lavoro svolto per un cliente in un'unica
    commessa (es. una nuova costruzione o una ristrutturazione), con un diario cronologico
    e gli interventi collegati. Gli interventi di cantiere <strong>non</strong> compaiono
    nella lista interventi della scheda cliente: restano dentro la scheda del cantiere.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-list-ul me-1"></i>Elenco e ricerca</h6>
<p>
    La voce <em>Cantieri</em> nel menu mostra tutti i cantieri con cliente, tipo e stato.
    Usa la casella di ricerca della tabella per filtrare per titolo o cliente; clicca le
    intestazioni di colonna per ordinare. Clicca una riga per aprire la scheda del cantiere.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Creare un cantiere</h6>
<p>Si può partire da due punti:</p>
<ul>
    <li>dal pulsante <strong>Nuovo</strong> nell'elenco cantieri;</li>
    <li>dalla <strong>scheda di un cliente</strong>: il cliente viene precompilato in automatico.</li>
</ul>
<p>
    Campi richiesti: <strong>cliente</strong>, <strong>titolo</strong>, <strong>tipo</strong>
    (nuova costruzione o ristrutturazione) e <strong>stato</strong>. Le date di inizio e di
    fine prevista sono facoltative. Dopo il salvataggio si apre la scheda del nuovo cantiere.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-card-text me-1"></i>Scheda cantiere</h6>
<p>La scheda raccoglie tre parti:</p>
<ul>
    <li><strong>Dati del cantiere</strong> e del cliente, con il pulsante per modificarli;</li>
    <li><strong>Diario</strong>: note cronologiche sull'avanzamento dei lavori;</li>
    <li><strong>Interventi collegati</strong>: gli interventi associati a questo cantiere.</li>
</ul>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Modifica e stato</h6>
<p>
    Il pulsante <strong>Modifica</strong> apre il form con tutti i dati. Lo
    <strong>stato</strong> (aperto, sospeso, chiuso) è solo un'etichetta organizzativa:
    cambiarlo <em>non</em> ha effetti sugli interventi già pianificati o completati. Chiudere
    un cantiere non chiude né elimina i suoi interventi.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-journal-text me-1"></i>Diario</h6>
<p>
    Aggiungi una nota dalla scheda indicando testo e, se vuoi, una data (di default oggi).
    Le note restano in ordine cronologico. Ogni nota può essere eliminata singolarmente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare un cantiere</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Un cantiere si può eliminare <strong>solo se non ha interventi collegati</strong>. Se ne
    ha, scollegali o eliminali prima. Le note del diario vengono invece eliminate insieme al
    cantiere. L'eliminazione è definitiva e non recuperabile.
</div>
