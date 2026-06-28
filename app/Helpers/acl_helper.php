<?php

/**
 * Helper ACL — regole di visibilità basate sui gruppi dell'utente loggato.
 */

if (! function_exists('is_solo_tecnico')) {
    /**
     * Vero se l'utente loggato è un tecnico "puro": appartiene al gruppo
     * tecnico e a nessun gruppo di gestione (admin, developer, ufficio).
     * Usato per la dashboard mobile-first e per ridurre la sidebar.
     */
    function is_solo_tecnico(): bool
    {
        if (! auth()->loggedIn()) {
            return false;
        }

        $gruppi = auth()->user()->getGroups();

        return in_array('tecnico', $gruppi, true)
            && ! array_intersect(['admin', 'developer', 'ufficio'], $gruppi);
    }
}
