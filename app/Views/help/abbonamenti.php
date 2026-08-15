<?php
/**
 * Guida della sezione Abbonamenti.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Un <strong>abbonamento</strong> è un contratto di manutenzione ricorrente per un cliente:
    genera interventi periodici (es. la manutenzione piscina) con una certa
    <strong>frequenza</strong>, senza doverli inserire a mano ogni volta.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-search me-1"></i>Elenco e stati</h6>
<p>L'elenco mostra cliente, tipo, frequenza e stato:</p>
<ul>
    <li><span class="badge bg-info text-dark">Proposta</span> creato ma non ancora accettato dal cliente, nessuna visita generata;</li>
    <li><span class="badge bg-success">Attivo</span> accettato e in corso, genera le visite previste;</li>
    <li><span class="badge bg-warning text-dark">Sospeso</span> temporaneamente in pausa;</li>
    <li><span class="badge bg-secondary">Scaduto</span> oltre la data di fine;</li>
    <li><span class="badge bg-danger">Disdetto</span> chiuso su richiesta del cliente dopo essere stato attivo;</li>
    <li><span class="badge bg-danger">Rifiutata</span> il cliente non ha accettato la proposta — non è mai stato un contratto attivo.</li>
</ul>
<p>
    Le <strong>frequenze</strong> vanno dalla settimanale all'annuale (settimanale,
    quindicinale, mensile, bimestrale, trimestrale, semestrale, annuale) e determinano ogni
    quanto è prevista la visita.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Creare un abbonamento</h6>
<p>
    Dal pulsante <strong>Nuovo</strong> (o dalla scheda cliente, che precompila il cliente)
    scegli tipo di intervento, frequenza, data di inizio ed eventuale fine. Da questi dati il
    sistema calcola le <strong>scadenze</strong> delle visite successive. Puoi anche indicare le
    <strong>operazioni incluse</strong> (precompilate dal tipo scelto, modificabili caso per
    caso) e le <strong>modalità di pagamento</strong> concordate — informazioni utili per il
    documento da consegnare al cliente. Un abbonamento appena creato nasce sempre come
    <strong>Proposta</strong>: non genera ancora nessuna visita.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-clipboard-check me-1"></i>Accettare o rifiutare una proposta</h6>
<p>
    Finché è in stato <strong>Proposta</strong>, dalla scheda o dall'elenco puoi
    <strong>accettarla</strong> (diventa Attiva e vengono generate tutte le visite previste)
    oppure <strong>rifiutarla</strong> (passa a Rifiutata, resta nello storico ma non genera
    nulla). Dall'elenco puoi anche selezionare più proposte con le caselle e accettarle tutte
    insieme col bottone in alto, utile a inizio anno quando ci sono molti rinnovi da confermare.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-calendar-event me-1"></i>Visite e prossima scadenza</h6>
<p>
    La scheda mostra la <strong>prossima visita</strong> in base alla scadenza e lo storico di
    quelle svolte. Le visite in scadenza entro il mese rientrano tra le "Scadenze aperte" del
    calendario e tra gli abbonamenti in scadenza della dashboard ufficio.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-square me-1"></i>Visite extra</h6>
<p>
    Oltre alle visite previste puoi registrare una <strong>visita extra</strong>: apre un
    nuovo intervento già intestato al cliente e al tipo dell'abbonamento, da pianificare come
    gli altri.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Stato e modifica</h6>
<p>
    Puoi <strong>sospendere</strong>, <strong>riattivare</strong> o <strong>disdire</strong>
    un abbonamento dalla scheda. Sospendere mette in pausa anche gli interventi collegati
    ancora da pianificare; riattivando tornano disponibili.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Per chiudere un contratto conviene <strong>disdirlo</strong> (resta nello storico) invece
    di eliminarlo. L'eliminazione è definitiva.
</div>
