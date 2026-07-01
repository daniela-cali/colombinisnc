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
    /** Promemoria che iniziano entro la fine di questa settimana (gli imminenti). */
    public array $questaSettimana = [];

    /** Promemoria successivi, entro i 14 giorni. */
    public array $prossimi = [];

    /** Numero di avvisi imminenti mostrato nel badge rosso. */
    public int $badge = 0;

    /**
     * Carica gli avvisi già divisi per fascia. Il badge conta solo gli imminenti
     * (quelli entro la fine di questa settimana).
     */
    public function mount(): void
    {
        $gruppi = (new PromemoriaModel())->inArrivoRaggruppati();

        $this->questaSettimana = $gruppi['settimana'];
        $this->prossimi        = $gruppi['prossimi'];
        $this->badge           = count($this->questaSettimana);
    }
}
