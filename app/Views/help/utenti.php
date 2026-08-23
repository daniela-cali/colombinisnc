<?php
/**
 * Guida della sotto-sezione Impostazioni → Utenti.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Questa pagina elenca <strong>tutti gli account</strong> che possono accedere alla
    piattaforma, con nominativo, email, <strong>gruppi</strong> di appartenenza e stato
    dell'accesso. È la vista di controllo centralizzata: risponde a <em>chi entra nel
    gestionale, con quali ruoli, e chi è ancora abilitato</em>.
</p>
<p>
    L'elenco è diviso in due schede: <strong>Personale</strong>, cioè chi lavora in azienda, e
    <strong>Clienti</strong>, per gli account dei clienti — quella dei clienti ha la casella di
    ricerca, perché è destinata a diventare lunga. Il numero accanto al nome della scheda dice
    quanti account contiene.
</p>
<p>
    È anche l'unico elenco che mostra gli account <strong>senza scheda dipendente</strong>,
    che in <em>Anagrafiche → Personale</em> non compaiono per definizione.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-people me-1"></i>Gruppi</h6>
<p>I gruppi disponibili sono Amministratore, Sviluppatore, Ufficio, Tecnico e Cliente. Determinano cosa l'utente vede e può fare nel gestionale.</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Dove si modifica un account</h6>
<p>
    Non da qui: questa pagina è di sola consultazione. <strong>Email, gruppi e password</strong>
    si modificano dalla scheda del dipendente in <em>Anagrafiche → Personale</em>, dove stanno
    insieme al resto dei suoi dati. Ognuno può cambiare i propri dal <strong>proprio profilo</strong>.
</p>

<div class="alert alert-info py-2 mb-0 mt-2">
    <i class="bi bi-info-circle me-1"></i>
    Per <strong>creare</strong> un nuovo utente parti da <em>Anagrafiche → Personale</em>:
    inserendo un dipendente vengono creati insieme la scheda e le credenziali.
</div>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-lock me-1"></i>Sospendere l'accesso</h6>
<p>
    <strong>Sospendi</strong> chiude l'accesso al gestionale: al tentativo di login la persona
    riceve un messaggio che la invita a rivolgersi a un amministratore. La scheda del dipendente,
    i suoi interventi e le sue assenze <strong>restano tutti al loro posto</strong>: cambia solo
    la possibilità di entrare. Con <strong>Riattiva</strong> si torna indietro in qualsiasi momento.
</p>
<p>
    Da sospeso, il dipendente non compare più fra i tecnici assegnabili a un nuovo intervento.
    Gli interventi <strong>già</strong> assegnati restano invece suoi — compresi quelli
    completati, che restano attribuiti a chi li ha fatti. Se ha lavoro ancora aperto, l'avviso
    te lo elenca subito con i link per riassegnarlo, e gli stessi interventi restano poi nella
    card <strong>Interventi in conflitto</strong> della dashboard, con l'etichetta
    <em>Account sospeso</em>, finché non li hai spostati su un altro tecnico.
</p>
<p class="mb-0">
    Non puoi sospendere l'account con cui stai lavorando: sulla tua riga il pulsante non compare.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Perché non si eliminano</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Gli account <strong>non si eliminano</strong>. Cancellare chi ha lavorato azzererebbe il
    tecnico su tutti i suoi interventi passati e cancellerebbe il suo diario delle assenze: lo
    storico dell'azienda vale più di un elenco corto. Per escludere qualcuno si <strong>sospende</strong>.
    Fa eccezione solo la scheda mai usata — l'errore di inserimento — che si elimina da
    <em>Anagrafiche → Personale</em>.
</div>
