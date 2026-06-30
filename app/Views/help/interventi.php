<?php
/**
 * Guida della sezione Interventi.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Gli <strong>Interventi</strong> sono i lavori svolti per i clienti. Il menu li divide per
    <strong>area</strong> (Generale, Piscine, Addolcitori…): ogni voce mostra solo gli
    interventi del proprio tipo. L'area "Generale" include anche gli interventi senza tipo.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-search me-1"></i>Elenco e stati</h6>
<p>Ogni intervento ha uno <strong>stato</strong> che ne segue il ciclo di vita:</p>
<ul>
    <li><span class="badge bg-light text-dark border">Da pianificare</span> appena creato, senza data;</li>
    <li><span class="badge bg-info text-dark">Pianificato</span> con data e tecnico assegnati;</li>
    <li><span class="badge bg-success">Completato</span> lavoro concluso;</li>
    <li><span class="badge bg-warning text-dark">Sospeso</span> in pausa (es. abbonamento sospeso);</li>
    <li><span class="badge bg-danger">Annullato</span> non sarà eseguito.</li>
</ul>
<p>
    La <strong>priorità</strong> può essere Normale o <strong>Urgente</strong>: gli urgenti
    risaltano nell'elenco, nel calendario e nella dashboard.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Creare un intervento</h6>
<p>
    Dal pulsante <strong>Nuovo</strong> scegli cliente e tipo di lavoro. Puoi indicare una
    <strong>data di scadenza</strong> (entro quando va fatto) senza ancora pianificarlo: resta
    "Da pianificare" finché non gli assegni data e tecnico dal calendario. Se apri il form
    dalla scheda di un cliente, di un cantiere o di un abbonamento, alcuni campi arrivano già
    precompilati.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-calendar-check me-1"></i>Pianificazione</h6>
<p>
    La pianificazione vera e propria si fa dal <strong>Calendario</strong>: gli interventi da
    pianificare compaiono nel pannello laterale e si trascinano su giorno, ora e tecnico.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-card-text me-1"></i>Scheda intervento</h6>
<p>Dalla scheda gestisci tutto il lavoro:</p>
<ul>
    <li><strong>Materiali</strong> da portare e usati (alcuni possono restare "sospesi" e si recuperano in seguito);</li>
    <li><strong>Diario</strong> con note datate sull'andamento;</li>
    <li>collegamento a un <strong>cantiere</strong> o a un <strong>abbonamento</strong>, mostrato con un banner.</li>
</ul>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Se un intervento è stato registrato per errore conviene <strong>annullarlo</strong>
    (resta nello storico) invece di eliminarlo. L'eliminazione è definitiva e rimuove anche
    materiali e note collegati.
</div>
