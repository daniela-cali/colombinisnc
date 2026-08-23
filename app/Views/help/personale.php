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
    Dal pulsante <strong>Nuovo</strong> inserisci nome, cognome, telefono e colore, insieme
    all'<strong>account</strong> con cui accederà: nome utente, email, password e almeno un
    <strong>gruppo</strong>. Nome utente ed email devono essere unici. Il pulsante compare solo
    agli amministratori, perché creare un dipendente significa creargli anche le credenziali.
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
    Dalla modifica aggiorni sempre l'<strong>anagrafica</strong> — nome, cognome, telefono,
    colore — e gestisci le assenze. Il blocco <strong>Account di accesso</strong> (email, gruppi
    e password) è riservato agli amministratori: chi non lo vede non è un errore della pagina.
    La <strong>password</strong> si cambia solo compilando il relativo campo: lasciandolo vuoto
    resta quella attuale, e ognuno può comunque cambiare la propria dal proprio profilo.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-lock me-1"></i>Chi non lavora più qui</h6>
<p>
    Non si elimina: si <strong>sospende l'accesso</strong> da <em>Impostazioni → Utenti</em>.
    La scheda e tutto il suo storico restano, ma la persona non entra più nel gestionale e non
    compare più fra i tecnici assegnabili a nuovi interventi.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    L'eliminazione serve solo a rimediare a un <strong>inserimento sbagliato</strong>: funziona
    unicamente sulle schede senza nessun intervento e nessuna assenza in archivio, e in quel caso
    cancella anche l'account. Su chi ha lavorato viene rifiutata, perché si perderebbe lo storico:
    il tecnico sparirebbe da tutti i suoi interventi passati.
</div>
