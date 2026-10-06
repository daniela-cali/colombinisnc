<?php

/**
 * Helper asset — indirizzi dei file statici (JS, CSS, librerie in assets/vendor)
 * con un numero di versione che cambia quando cambia il file.
 *
 * Senza, il browser continua a usare la copia in cache di un file modificato: con
 * DataTables 3 il vecchio datatable-init.js chiamava ancora jQuery, ormai tolto, e
 * le tabelle restavano senza ricerca né paginazione finché non si svuotava la cache.
 */

if (! function_exists('asset_url')) {
    /**
     * Restituisce l'URL del file in public/ seguito da ?v=<data di ultima modifica>.
     *
     * Si usa la data del file e non la versione dell'app: a ogni rilascio il browser
     * riscarica solo i file cambiati, e non c'è niente da aggiornare a mano. Un file
     * inesistente riceve l'URL senza versione, così il browser ottiene il solito 404
     * invece di una pagina bloccata da un errore PHP.
     */
    function asset_url(string $percorso): string
    {
        $url  = base_url($percorso);
        $data = @filemtime(FCPATH . ltrim($percorso, '/'));

        return $data === false ? $url : $url . '?v=' . $data;
    }
}
