<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\NumeratoriModel;

class NumeratoriController extends BaseController
{
    /**
     * Elenco dei contatori progressivi, in sola lettura.
     *
     * Serve a sapere a che numero è arrivata una serie senza interrogare a mano la tabella
     * settings. Non c'è modifica dall'interfaccia di proposito: il caso reale — riallineare
     * un contatore dopo un import massivo — è raro e si esegue direttamente sul database,
     * mentre un numero abbassato per errore produrrebbe codici duplicati e salvataggi
     * che falliscono. Vedi docs/spec/numeratori_atomici_spec.md, decisione 8.
     */
    public function index(): string
    {
        return view('impostazioni/numeratori', [
            'title'        => 'Numeratori',
            'numeratori'   => (new NumeratoriModel())->elenco(),
            'help_sezione' => 'impostazioni',
        ]);
    }
}
