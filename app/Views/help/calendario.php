<?php
/**
 * Guida della sezione Calendario.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Il <strong>Calendario</strong> serve a pianificare gli interventi: assegnare a ciascuno
    un giorno, un orario e un tecnico. A sinistra c'è il pannello degli interventi
    <strong>da pianificare</strong>, a destra l'agenda.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-inbox me-1"></i>Pannello "Da pianificare"</h6>
<p>
    Raccoglie gli interventi ancora senza data, raggruppati per <strong>zona</strong> e
    ordinati per urgenza, scadenza e distanza dalla sede. Ogni card mostra cliente, tipo,
    scadenza ed eventuale distanza. Il pannello si può <strong>comprimere</strong> a icona
    (clic sull'intestazione) per dare più spazio al calendario, e <strong>ridimensionare</strong>
    trascinando il bordo destro. Su smartphone è nascosto: lì si pianifica aprendo l'intervento.
</p>
<p>
    Le visite ricorrenti da <strong>abbonamento</strong> compaiono nel pool solo quando si
    avvicina la loro scadenza: restano nascoste finché non rientrano nella settimana (o giorno,
    su mobile) che stai guardando sul calendario, e si aggiornano da sole navigando con le
    frecce avanti/indietro. Interventi normali, visite extra e occorrenze già in ritardo restano
    invece sempre visibili, qualunque periodo tu stia guardando.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrows-move me-1"></i>Pianificare con il trascinamento</h6>
<p>
    <strong>Trascina</strong> una card dal pannello al punto desiderato del calendario: si apre
    una finestra per confermare orario e tecnico, la durata viene proposta dal tipo di
    intervento e l'intervento passa a "Pianificato". Puoi poi <strong>spostarlo</strong> o
    <strong>allungarlo</strong> direttamente sul calendario; cliccandolo apri la scheda
    dell'intervento. Per rimetterlo in coda, rimuovilo dalla pianificazione.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-exclamation-triangle-fill me-1"></i>Barra "Attenzione"</h6>
<p>
    Sopra il calendario, quando c'è qualcosa da segnalare, compare la barra
    <strong>Attenzione</strong> con fino a tre pill (ciascuna con contatore e tooltip che ne
    spiega il criterio):
</p>
<ul>
    <li><span class="badge badge-scadenza-mancato fw-normal"><i class="bi bi-calendar-x me-1"></i>Non completato</span>
        — appuntamenti con data ormai passata, mai chiusi né annullati;</li>
    <li><span class="badge badge-scadenza-ritardo fw-normal"><i class="bi bi-clock me-1"></i>In ritardo</span>
        — interventi con la scadenza superata, qualunque sia il loro stato;</li>
    <li><span class="badge badge-scadenza-fermo fw-normal"><i class="bi bi-hourglass-split me-1"></i>Fermo</span>
        — da pianificare da più di 7 giorni, senza ancora una scadenza superata.</li>
</ul>
<p>
    Cliccando una pill si apre l'elenco; cliccando un nome in elenco, se l'intervento è da
    pianificare si apre il pool con la card evidenziata, se è già pianificato il calendario
    salta alla sua data — nessuna navigazione fuori pagina.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-exclamation-triangle me-1"></i>Avviso di scadenza superata</h6>
<div class="alert alert-info py-2 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Quando pianifichi <strong>un qualsiasi intervento dotato di scadenza</strong> in un giorno
    successivo a quella scadenza, nella finestra di pianificazione compare un avviso:
    <strong>rosso</strong> se l'intervento è classificato come
    <span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgente</span>
    nella scheda intervento, <strong>giallo</strong> negli altri casi.
    Non è bloccante — puoi comunque procedere: è solo un promemoria che la data scelta è oltre
    la scadenza.
</div>
