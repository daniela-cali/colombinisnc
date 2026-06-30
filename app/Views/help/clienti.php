<?php
/**
 * Guida della sezione Clienti.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    L'anagrafica <strong>Clienti</strong> è il cuore del gestionale: da qui partono
    interventi, abbonamenti e cantieri. Ogni cliente può essere una <strong>persona
    fisica</strong> (nome e cognome) o una <strong>società</strong> (ragione sociale).
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-search me-1"></i>Ricerca ed elenco</h6>
<p>
    L'elenco mostra denominazione, recapiti, città e tecnico preferito. Usa la casella di
    ricerca per filtrare al volo e le intestazioni di colonna per ordinare. Su smartphone le
    colonne meno importanti si nascondono in una riga espandibile (tocca il <code>+</code>).
    Clicca una riga per aprire la scheda del cliente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Inserire un cliente</h6>
<p>
    Dal pulsante <strong>Nuovo</strong> scegli prima il <strong>tipo</strong>: per una società
    è obbligatoria la <strong>ragione sociale</strong>, per una persona fisica
    <strong>nome e cognome</strong>. Email e indirizzo sono facoltativi ma consigliati:
    l'indirizzo viene <strong>geocodificato</strong> (posizionato sulla mappa) per calcolare
    distanze e percorsi. Il <strong>codice cliente</strong> viene generato automaticamente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-geo-alt me-1"></i>Zona e geocodifica</h6>
<p>
    La <strong>zona</strong> (Ovest, Centro, Est) viene proposta in base alla posizione
    geografica e serve a raggruppare gli interventi nel calendario. Se la geocodifica
    fallisce puoi rilanciarla dalla scheda dopo aver corretto l'indirizzo.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-card-text me-1"></i>Scheda cliente</h6>
<p>La scheda raccoglie in un'unica pagina scorrevole:</p>
<ul>
    <li><strong>Interventi</strong> del cliente (esclusi quelli dentro un cantiere);</li>
    <li><strong>Abbonamenti</strong> attivi e il loro stato;</li>
    <li><strong>Cantieri</strong> collegati;</li>
    <li><strong>Materiali sospesi</strong> da recuperare e lo storico materiali.</li>
</ul>
<p>Da qui puoi creare direttamente un nuovo intervento, abbonamento o cantiere già intestato al cliente.</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Modifica</h6>
<p>
    Il pulsante <strong>Modifica</strong> apre il form con i dati anagrafici. Le modifiche
    all'indirizzo possono richiedere una nuova geocodifica.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare un cliente</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    L'eliminazione è <strong>definitiva</strong>. Prima di eliminare un cliente verifica che
    non abbia interventi, abbonamenti o cantieri collegati: in caso contrario rischi di
    perdere dati storici. In alternativa puoi disattivarlo invece di cancellarlo.
</div>
