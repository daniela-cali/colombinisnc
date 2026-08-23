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

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-person-x me-1"></i>Assenze di oggi</h6>
<p>
    Chi <strong>non è al lavoro oggi</strong>, con il tipo di assenza accanto al nome: ci finisce
    ogni assenza il cui periodo comprende la data odierna, non solo quelle che iniziano oggi.
    Cliccando il nome apri la scheda del dipendente. Il riquadro resta <strong>verde</strong>
    quando non manca nessuno.
</p>
<p>
    Le assenze si registrano dalla scheda del dipendente, in <em>Anagrafiche → Personale</em>.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-bell me-1"></i>Promemoria in arrivo</h6>
<p>
    I promemoria dei <strong>prossimi quattordici giorni</strong>, divisi in <em>Oggi</em> e
    <em>Prossimi giorni</em>, con l'orario a destra e le eventuali note sotto il titolo. Cliccando
    il titolo si apre il calendario sul giorno del promemoria. La card ne mostra al massimo cinque
    per fascia: se ce ne sono altri, il collegamento in fondo lo dice.
</p>
<p>
    Un promemoria già letto resta in elenco con una <strong>spunta verde</strong> accanto
    all'orario: viene segnalato, non nascosto — serve a ricordarti che c'è, non a sparire appena
    lo guardi. Un promemoria di oggi resta visibile anche quando l'orario è passato.
</p>
<p>
    Attenzione a una differenza: il badge della <strong>campanella</strong> nella barra in alto
    conta solo quelli di <strong>oggi</strong>, mentre questa card li conta tutti e quattordici i
    giorni. Due numeri diversi non sono un errore.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-exclamation-octagon me-1"></i>Interventi in conflitto</h6>
<p>
    Interventi <strong>già pianificati</strong> che il tecnico assegnato non è più in grado di
    fare. Il badge accanto al titolo dice quanti sono, ed è verde quando non ce n'è nessuno.
    L'etichetta colorata su ogni riga spiega il motivo:
</p>
<ul>
    <li>
        <span class="badge bg-warning-subtle text-warning-emphasis">Ferie</span>,
        <span class="badge bg-warning-subtle text-warning-emphasis">Malattia</span>… — il tecnico
        risulta <strong>assente</strong> proprio quel giorno, perché l'assenza è stata registrata
        dopo che l'intervento era già stato messo in calendario;
    </li>
    <li>
        <span class="badge bg-danger-subtle text-danger-emphasis">Account sospeso</span> — al
        tecnico è stato <strong>chiuso l'accesso</strong> al gestionale, quindi quel lavoro non
        lo prenderà in carico nessuno.
    </li>
</ul>
<p>
    In entrambi i casi l'intervento resta assegnato a lui finché non lo sposti tu: cliccando la
    riga apri l'intervento e scegli un altro tecnico. Sistemato quello, la riga sparisce da sola —
    e sparisce anche se l'assenza viene cancellata o l'account riattivato.
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
