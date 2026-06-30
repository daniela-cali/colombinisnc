<?php
/**
 * Guida della sezione Foglio di viaggio.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Il <strong>Foglio di viaggio</strong> mostra, per una singola giornata, tutti gli
    interventi <strong>pianificati</strong> raggruppati per <strong>zona</strong>: è il
    riepilogo operativo da consegnare o stampare per organizzare le uscite dei tecnici.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-calendar3 me-1"></i>Scegliere il giorno</h6>
<p>
    In alto puoi spostarti tra i giorni con le frecce <strong>precedente / successivo</strong>;
    di default si apre su oggi. Vengono mostrati tutti gli interventi del giorno tranne quelli
    annullati, in ordine di orario.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-geo-alt me-1"></i>Raggruppamento per zona</h6>
<p>
    Gli interventi sono divisi per zona del cliente (Ovest, Centro, Est e "Senza zona") per
    ottimizzare gli spostamenti. Per ciascuno vedi orario, cliente, indirizzo, tecnico, tipo di
    lavoro e i <strong>materiali da portare</strong> con le quantità.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-file-earmark-pdf me-1"></i>Stampa e PDF</h6>
<p>
    Dal pulsante dedicato generi il <strong>PDF</strong> del foglio (formato orizzontale A4),
    pronto da stampare o inviare ai tecnici.
</p>

<div class="alert alert-info py-2 mb-0 mt-2">
    <i class="bi bi-info-circle me-1"></i>
    Il foglio riflette la pianificazione del calendario: se mancano interventi attesi,
    verifica che siano stati pianificati su quella data.
</div>
