<?php
/**
 * Guida della sezione Personale.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    La sezione <strong>Personale</strong> gestisce i dipendenti dell'azienda e, per chi deve
    accedere al gestionale, il relativo <strong>account</strong>. A ogni dipendente è associato
    un <strong>colore</strong> usato per riconoscerlo a colpo d'occhio nel calendario.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-search me-1"></i>Elenco</h6>
<p>
    L'elenco mostra tutto il personale con account collegato e gruppi di appartenenza. Clicca
    una riga per aprire la scheda del dipendente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Inserire un dipendente</h6>
<p>
    Dal pulsante <strong>Nuovo</strong> inserisci nome, cognome, telefono e colore. Per dargli
    accesso al gestionale crei contestualmente l'<strong>account</strong>: nome utente, email,
    password e almeno un <strong>gruppo</strong>. Il nome utente deve essere unico.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-people me-1"></i>Gruppi e permessi</h6>
<p>I gruppi determinano cosa l'utente può vedere e fare:</p>
<ul>
    <li><strong>Ufficio</strong>: gestione clienti, interventi, abbonamenti, calendario;</li>
    <li><strong>Tecnico</strong>: vista ridotta orientata al lavoro sul campo (agenda, interventi propri);</li>
    <li><strong>Amministratore</strong>: accesso completo, incluse impostazioni;</li>
    <li><strong>Sviluppatore</strong>: come admin, più la documentazione tecnica e le note di sviluppo.</li>
</ul>
<p>Un dipendente può appartenere a più gruppi (es. admin + tecnico).</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Modifica e password</h6>
<p>
    Dalla modifica aggiorni anagrafica, email e gruppi. La <strong>password</strong> si cambia
    solo se compili il relativo campo: lasciandolo vuoto resta quella attuale.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Eliminare un dipendente rimuove anche il suo account di accesso. Verifica prima che non
    abbia interventi collegati come tecnico: in alternativa puoi togliergli i gruppi per
    revocare l'accesso senza cancellarlo.
</div>
