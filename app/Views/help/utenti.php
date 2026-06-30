<?php
/**
 * Guida della sotto-sezione Impostazioni → Utenti.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Questa pagina elenca <strong>tutti gli account</strong> che possono accedere alla
    piattaforma, con il nominativo (preso dall'anagrafica Personale) e i <strong>gruppi</strong>
    di appartenenza. È la vista di controllo centralizzata sugli accessi.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-people me-1"></i>Gruppi</h6>
<p>I gruppi disponibili sono Amministratore, Sviluppatore, Ufficio, Tecnico e Cliente. Determinano cosa l'utente vede e può fare nel gestionale.</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Modificare un utente</h6>
<p>
    Dalla modifica puoi aggiornare <strong>email</strong>, <strong>gruppi</strong> e
    <strong>password</strong> (solo se compili il campo: lasciandolo vuoto resta quella
    attuale). <strong>Nome e cognome</strong> non si toccano qui: si gestiscono in
    <em>Anagrafiche → Personale</em>, perché appartengono alla scheda del dipendente.
</p>

<div class="alert alert-info py-2 mb-0 mt-2">
    <i class="bi bi-info-circle me-1"></i>
    Per <strong>creare</strong> un nuovo utente parti da <em>Anagrafiche → Personale</em>:
    inserendo un dipendente con account vengono creati insieme la scheda e le credenziali.
    Questa pagina serve a gestire gli account già esistenti.
</div>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Eliminare un account revoca l'accesso. La scheda del dipendente in Personale resta, ma
    senza credenziali collegate. Se vuoi solo sospendere l'accesso, togli i gruppi invece di
    cancellare l'account.
</div>
