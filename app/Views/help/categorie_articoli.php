<?php
/**
 * Guida della sotto-sezione Impostazioni → Categorie articoli.
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Le <strong>categorie articoli</strong> raggruppano il catalogo di magazzino (es. Prodotti,
    Attrezzature, Apparecchiature, Ricambi). Servono a tenere ordinati gli articoli e a
    filtrarli più facilmente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-list-ul me-1"></i>Elenco e ordine</h6>
<p>
    Ogni categoria ha un <strong>nome</strong> e un <strong>ordine</strong>, che ne determina
    la posizione nelle liste. Il prossimo numero d'ordine viene proposto automaticamente.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Creare e modificare</h6>
<p>
    Il <strong>form inline</strong> in cima alla pagina crea una nuova categoria; nome e ordine
    delle categorie esistenti si modificano direttamente nella tabella.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-trash me-1"></i>Eliminare</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Una categoria si può eliminare <strong>solo se non contiene articoli</strong>. Se ha
    articoli collegati, spostali prima in un'altra categoria: il sistema blocca l'eliminazione
    indicando quanti articoli contiene.
</div>
