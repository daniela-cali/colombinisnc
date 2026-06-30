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

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrows-move me-1"></i>Pianificare con il trascinamento</h6>
<p>
    <strong>Trascina</strong> una card dal pannello al punto desiderato del calendario: si apre
    una finestra per confermare orario e tecnico, la durata viene proposta dal tipo di
    intervento e l'intervento passa a "Pianificato". Puoi poi <strong>spostarlo</strong> o
    <strong>allungarlo</strong> direttamente sul calendario; cliccandolo apri la scheda
    dell'intervento. Per rimetterlo in coda, rimuovilo dalla pianificazione.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-clock-history me-1"></i>Scadenze aperte</h6>
<p>
    La fascia <strong>Scadenze aperte</strong> evidenzia gli interventi con una scadenza da
    rispettare ancora non pianificati: serve a non perdere di vista le consegne. Il tooltip
    sull'intestazione spiega esattamente cosa mostra.
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
