<?php

namespace App\Controllers;

use App\Models\PersonaleModel;

class ProfiloController extends BaseController
{
    /**
     * Redirige l'utente loggato alla propria scheda in anagrafica personale.
     */
    public function index()
    {
        $persona = (new PersonaleModel())->perUtente(user_id());

        if (! $persona) {
            return redirect()->to('/')->with('error', 'Nessuna scheda personale associata al tuo account.');
        }

        return redirect()->to('anagrafiche/personale/' . $persona['id'] . '/edit');
    }

    /**
     * Aggiorna ultima_versione_vista per l'utente loggato.
     * Chiamato in AJAX dal modal novità versione.
     */
    public function versioneVista()
    {
        $versione = $this->request->getPost('versione');

        if ($versione) {
            $users = auth()->getProvider();
            $user  = auth()->user();
            $user->ultima_versione_vista = $versione;
            $users->save($user);
        }

        return $this->response->setJSON(['ok' => true]);
    }
}
