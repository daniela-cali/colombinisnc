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
    <li><span class="badge bg-secondary">Scaduto</span> oltre la data di fine — scade anche un
        abbonamento sospeso, perché la pausa ferma le visite ma non allunga il contratto;</li>
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
<p>
    La <strong>disdetta</strong> chiude il contratto e annulla tutte le visite successive a
    oggi, <strong>comprese quelle già pianificate in calendario</strong>: in quel caso il
    messaggio te lo segnala, perché il cliente conosce già la data e va avvisato con una
    telefonata. Le visite arretrate — scadute e mai effettuate — restano invece come sono: se
    non servono più, annullale una per una dalla loro scheda.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrow-counterclockwise me-1"></i>Correggere un'accettazione sbagliata</h6>
<p>
    Se accetti una proposta e ti accorgi di un errore — date sbagliate, frequenza sbagliata,
    cliente sbagliato — usa <strong>Annulla accettazione</strong> dalla scheda. L'abbonamento
    torna in <strong>Proposta</strong> e le visite generate all'accettazione vengono cancellate:
    da lì correggi quello che serve e riaccetti, e le visite si rigenerano dai periodi corretti.
    Non esiste un comando per rigenerare le visite senza passare da qui, ed è voluto: una
    proposta non ha mai visite collegate, quindi non c'è modo di ritrovarsi con visite vecchie
    e periodi nuovi che non corrispondono.
</p>
<p>
    L'operazione viene <strong>rifiutata</strong> se anche una sola visita è già stata
    pianificata, è in corso o è stata completata: il messaggio ti elenca quali sono, con il
    link per aprirle. Sposta o annulla quelle visite e riprova. Se invece il lavoro è stato
    fatto davvero, allora non è un errore da correggere: il contratto è reale, e per chiuderlo
    si usa la disdetta.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-arrow-repeat me-1"></i>Rinnovare</h6>
<p>
    Il <strong>rinnovo</strong> apre un nuovo abbonamento precompilato con i dati di quello
    attuale e le date spostate di un anno, da controllare e salvare come proposta. Non serve
    aspettare la scadenza: puoi prepararlo quando vuoi, anche mentre il contratto è ancora in
    corso — è il caso degli abbonamenti annuali che si preparano a dicembre per l'anno dopo.
    Il pulsante non compare su un abbonamento <strong>sospeso</strong> (prima va riattivato),
    su uno non ancora cominciato, e su uno già rinnovato: in quest'ultimo caso trovi al suo
    posto il collegamento <strong>Vai al rinnovo</strong>.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<p>
    Si elimina solo un abbonamento in stato <strong>Proposta</strong>, che non ha visite
    collegate. Per un contratto già accettato la via è un'altra: se è un errore, annulla prima
    l'accettazione e poi elimina la proposta; se è un contratto vero che finisce,
    <strong>disdicilo</strong>, così resta nello storico con le sue visite svolte.
</p>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    L'eliminazione è definitiva e cancella anche i periodi dell'abbonamento. Un abbonamento già
    rinnovato non si elimina finché esiste il rinnovo che lo indica come precedente.
</div>
