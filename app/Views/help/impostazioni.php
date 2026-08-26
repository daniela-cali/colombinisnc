<?php
/**
 * Guida della sezione Impostazioni.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Le <strong>Impostazioni</strong> raccolgono la configurazione del gestionale, divisa in
    aree. Da questa pagina-indice si accede a ciascuna scheda cliccando la relativa card.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-sliders me-1"></i>Parametri generali</h6>
<p>
    Dati dell'azienda e della <strong>sede</strong> (indirizzo, coordinate, recapiti),
    <strong>orari</strong> di lavoro e pausa, <strong>logo</strong> e le soglie geografiche che
    determinano l'assegnazione automatica della <strong>zona</strong> ai clienti. Sono i dati
    usati per calcolare distanze, percorsi e per intestare i documenti.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-map me-1"></i>Geocodifica clienti</h6>
<p>
    Strumento per verificare e aggiornare in blocco le <strong>coordinate</strong> dei clienti,
    utile dopo importazioni o correzioni di indirizzi.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-list-check me-1"></i>Tipi intervento</h6>
<p>
    I <strong>tipi di intervento</strong> (es. manutenzione piscina, addolcitore, filtri) con
    la loro <strong>area</strong> e la <strong>durata standard</strong> usata in fase di
    pianificazione. Aggiungere un tipo qui lo rende disponibile in interventi e abbonamenti.
</p>
<p>
    Il <strong>prefisso</strong> di tre lettere apre i codici degli interventi di quel tipo
    (<code>PIS-0400</code>, <code>SAL-0001</code>), sia che nascano da un abbonamento sia che
    vengano inseriti a mano. Chi non ne ha usa <code>INT</code>. Ogni prefisso ha una
    numerazione propria, che trovi in <strong>Numeratori</strong>.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-tags me-1"></i>Categorie articoli</h6>
<p>
    I raggruppamenti del catalogo magazzino (Prodotti, Attrezzature, Ricambi…). Servono a
    ordinare gli articoli.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-person-gear me-1"></i>Utenti</h6>
<p>
    Vista di <strong>tutti gli account</strong> con accesso alla piattaforma, dei loro gruppi e
    del loro stato, divisa nelle due schede <strong>Personale</strong> e <strong>Clienti</strong>.
    È di sola consultazione: l'unica azione è <strong>sospendere o riaprire l'accesso</strong>.
    Email, ruoli e password si modificano dalla scheda del dipendente in <em>Personale</em>, da
    dove si crea anche un nuovo dipendente con il suo account.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-123 me-1"></i>Numeratori</h6>
<p>
    Mostra a che punto è arrivata ogni serie di codici — clienti creati nel gestionale,
    interventi manuali, e una serie per ogni tipo di intervento da abbonamento — con l'ultimo
    codice assegnato, il prossimo che verrà generato e la data dell'ultimo utilizzo.
</p>
<p>
    I numeri crescono sempre e non tornano mai indietro: un codice assegnato resta speso anche
    se quel cliente o quell'intervento viene poi eliminato, così lo stesso codice non finisce
    mai su due documenti diversi. La pagina è di <strong>sola consultazione</strong>; se una
    serie va riallineata, per esempio dopo un caricamento massivo di dati, l'intervento si fa
    direttamente sul database.
</p>

<div class="alert alert-info py-2 mb-0 mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Le card grigie contrassegnate <strong>"In costruzione"</strong> sono funzionalità previste
    ma non ancora attive.
</div>
