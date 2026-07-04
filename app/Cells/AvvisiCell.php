<?php

namespace App\Cells;

use App\Models\PromemoriaModel;
use CodeIgniter\View\Cells\Cell;

/**
 * Campanella "avvisi" in navbar. Aggregatore degli avvisi da mostrare
 * all'utente: oggi solo i promemoria, in futuro anche le notifiche vere.
 */
class AvvisiCell extends Cell
{
    /** Promemoria di oggi. */
    public array $oggi = [];

    /** Promemoria successivi, entro i 14 giorni. */
    public array $prossimi = [];

    /** Numero di avvisi di oggi mostrato nel badge rosso. */
    public int $badge = 0;

    /**
     * Carica gli avvisi già divisi per fascia. Il badge conta solo quelli di oggi,
     * indipendentemente dal fatto che l'utente li abbia già chiusi nella modal
     * (il dismiss riguarda solo il popup forzato, non la campanella).
     */
    public function mount(): void
    {
        $gruppi = (new PromemoriaModel())->inArrivoRaggruppati((int) user_id());

        $this->oggi     = $gruppi['oggi'];
        $this->prossimi = $gruppi['prossimi'];
        $this->badge    = count($this->oggi);
    }
}
