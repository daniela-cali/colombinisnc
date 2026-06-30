<?php
/**
 * Guida della sezione Articoli (magazzino).
 * Partial incluso nel corpo del modal #modalHelp dal layout admin.
 */
?>
<p class="text-muted">
    Gli <strong>Articoli</strong> sono il catalogo di prodotti, attrezzature e ricambi usati
    negli interventi. Avere un catalogo ordinato permette di aggiungere i materiali a un
    intervento scegliendoli da un elenco, invece di riscriverli ogni volta.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-search me-1"></i>Elenco</h6>
<p>
    L'elenco mostra descrizione, categoria e unità di misura di ogni articolo. Usa la ricerca
    per trovarli rapidamente e le intestazioni per ordinare.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-plus-circle me-1"></i>Inserire un articolo</h6>
<p>
    Dal pulsante <strong>Nuovo</strong> indichi descrizione, <strong>categoria</strong> e
    <strong>unità di misura</strong> (pezzi, litri, kg…). La categoria serve a raggruppare il
    catalogo e si gestisce da <em>Impostazioni → Categorie Articoli</em>.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-box-seam me-1"></i>Uso negli interventi</h6>
<p>
    Quando aggiungi materiali a un intervento puoi selezionare un articolo dal catalogo: la
    descrizione e l'unità di misura vengono prese da qui. È comunque possibile inserire un
    materiale "libero" non a catalogo per i casi occasionali.
</p>

<h6 class="border-bottom pb-1 mt-4"><i class="bi bi-pencil me-1"></i>Modifica ed eliminazione</h6>
<div class="alert alert-warning py-2 mb-0">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Modificare la descrizione di un articolo aggiorna il riferimento ovunque sia usato. Prima
    di eliminarne uno verifica che non sia collegato a interventi: meglio correggerlo che
    cancellarlo se è già stato impiegato.
</div>
