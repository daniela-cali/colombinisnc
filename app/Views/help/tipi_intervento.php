<?php
/**
 * Guida della sotto-sezione Impostazioni → Tipi intervento.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    I <strong>tipi di intervento</strong> sono le categorie di lavoro che svolgete (es.
    manutenzione piscina, addolcitore, filtri). Definirli qui permette di sceglierli quando
    crei interventi e abbonamenti, e di avere durata e icona coerenti ovunque.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-list-ul me-1"></i>Elenco e campi</h6>
<p>Ogni tipo ha:</p>
<ul>
    <li><strong>Codice</strong>: identificativo univoco e senza spazi (lettere, numeri, trattini);</li>
    <li><strong>Nome</strong>: come appare nelle liste e nei menu;</li>
    <li><strong>Area</strong>: il raggruppamento della sidebar Interventi (Generale, Piscine, Addolcitori…); se non impostata ricade in Generale;</li>
    <li><strong>Icona</strong>: nome di un'icona Bootstrap Icons (facoltativo);</li>
    <li><strong>Durata standard</strong>: minuti proposti in pianificazione sul calendario;</li>
    <li><strong>Ordine</strong>: posizione nelle liste;</li>
    <li><strong>Abbonabile</strong>: se spuntato, il tipo è selezionabile quando si crea un abbonamento;</li>
    <li><strong>Prevede pulizia fondo</strong>: mostra l'opzione pulizia fondo su interventi e periodi di questo tipo;</li>
    <li><strong>Operazioni standard</strong>: testo libero con l'elenco delle operazioni tipiche di questo tipo (es. le voci di manutenzione previste), visibile solo se il tipo è Abbonabile. Serve come base di partenza: ogni abbonamento la eredita alla creazione e può poi correggerla per il singolo cliente.</li>
</ul>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Creare e modificare</h6>
<p>
    Il <strong>form inline</strong> in cima alla pagina aggiunge un nuovo tipo; le righe
    esistenti si modificano dal bottone <i class="bi bi-pencil"></i> di ogni riga. Il codice deve
    restare unico.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Un tipo intervento si può eliminare <strong>solo se non è usato</strong> in alcun
    intervento. Se è già stato impiegato, il sistema blocca l'eliminazione indicando quanti
    interventi lo usano: in quel caso modificalo invece di cancellarlo.
</div>
