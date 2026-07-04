<?php

namespace App\Cells;

use App\Models\PromemoriaModel;
use CodeIgniter\View\Cells\Cell;

/**
 * Modal informativa forzata con i promemoria di oggi non ancora visti
 * dall'utente corrente. Si apre da sola ad ogni accesso finché l'utente
 * non clicca "Ho letto" (che li segna come visti su promemoria_dismiss).
 */
class PromemoriaOggiCell extends Cell
{
    /** Promemoria di oggi non ancora chiusi dall'utente loggato. */
    public array $oggi = [];

    public function mount(): void
    {
        $this->oggi = (new PromemoriaModel())->oggiNonVisti((int) user_id());
    }
}
