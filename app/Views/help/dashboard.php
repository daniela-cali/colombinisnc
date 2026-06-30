<?php
/**
 * Guida della Dashboard (versione ufficio/admin).
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    La <strong>Dashboard</strong> è la pagina iniziale e si adatta al tuo ruolo: mostra a
    colpo d'occhio cosa richiede attenzione oggi. Ogni riquadro è un collegamento rapido alla
    sezione corrispondente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-calendar-check me-1"></i>Interventi di oggi</h6>
<p>
    Il conteggio degli interventi <strong>pianificati per oggi</strong>, con i collegamenti al
    calendario e al foglio di viaggio della giornata.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-exclamation-triangle me-1"></i>Urgenti da pianificare</h6>
<p>
    La lista degli interventi <strong>urgenti</strong> ancora senza data: vanno presi in
    carico e pianificati dal calendario il prima possibile. Cliccando un elemento apri il
    relativo intervento.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-file-earmark-text me-1"></i>Abbonamenti in scadenza <span class="badge bg-secondary">solo Ufficio</span></h6>
<p>
    Per il gruppo Ufficio, una tabella degli <strong>abbonamenti in scadenza nei prossimi 30
    giorni</strong>, ordinati per data, con un badge che indica i giorni rimasti
    (<span class="badge bg-danger">≤7gg</span> <span class="badge bg-warning text-dark">≤15gg</span>
    <span class="badge bg-secondary">oltre</span>).
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-person-badge me-1"></i>I miei interventi <span class="badge bg-secondary">se sei anche tecnico</span></h6>
<p>
    Se oltre al ruolo d'ufficio sei anche tecnico, vedi una sezione personale con i
    <strong>tuoi interventi di oggi</strong> e i <strong>tuoi urgenti</strong> da pianificare.
</p>

<div class="alert alert-info py-2 mb-0 mt-3">
    <i class="bi bi-info-circle me-1"></i>
    I tecnici "puri" vedono invece un'agenda dedicata e ottimizzata per lo smartphone, con la
    sua guida specifica.
</div>
