<?php

namespace App\Controllers;

use App\Controllers\Anagrafiche\PersonaleController;
use App\Models\PersonaleModel;
use App\Models\UserModel;

class ProfiloController extends BaseController
{
    /**
     * Form di modifica del proprio profilo: anagrafica ed account,
     * niente gruppi (a differenza del form admin di Anagrafiche\PersonaleController).
     *
     * La scheda personale è facoltativa: un account senza dipendente collegato — oggi
     * nessuno, domani quelli del portale clienti — deve comunque poter cambiare la
     * propria email e la propria password.
     */
    public function index()
    {
        $persona = (new PersonaleModel())->perUtente(user_id());
        $user    = (new UserModel())->findById(user_id());

        return view('profilo/index', [
            'persona'      => $persona,
            'user'         => $user,
            'email'        => $user?->getEmailIdentity()?->secret ?? '',
            'pastelli'     => PersonaleController::PASTELLI,
            'colori_usati' => $persona ? (new PersonaleModel())->coloriUsati($persona['id']) : [],
        ]);
    }

    /**
     * Aggiorna anagrafica, email e password opzionale del proprio profilo.
     * Il dipendente da aggiornare è sempre risolto da user_id() lato server,
     * mai da un id in POST/URL: nessun altro profilo è raggiungibile da qui.
     */
    public function update()
    {
        $persona = (new PersonaleModel())->perUtente(user_id());

        $rules = [
            'email' => 'required|valid_email|max_length[254]|is_unique[auth_identities.secret,user_id,' . user_id() . ']',
        ];

        if ($persona) {
            $rules['nome']     = 'required|max_length[100]';
            $rules['cognome']  = 'required|max_length[100]';
            $rules['telefono'] = 'permit_empty|max_length[20]';
            $rules['colore']   = 'permit_empty|max_length[7]';
        }

        if ($this->request->getPost('password')) {
            $rules['password']         = 'min_length[8]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (! $this->validate($rules, [
            'email'            => ['is_unique' => 'Questa email è già associata a un altro account.'],
            'password_confirm' => ['matches'   => 'Le password non coincidono.'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($persona) {
            (new PersonaleModel())->update($persona['id'], [
                'nome'     => $this->request->getPost('nome'),
                'cognome'  => $this->request->getPost('cognome'),
                'telefono' => $this->request->getPost('telefono') ?: null,
                'colore'   => $this->request->getPost('colore') ?: null,
            ]);
        }

        // Elenco dei gruppi assegnabili vuoto: dal proprio profilo non si cambiano i ruoli,
        // e il model ignora i gruppi anche se la richiesta prova a includerli.
        $users = new UserModel();
        $users->aggiornaAccount($users->findById(user_id()), $this->request->getPost(), []);

        return redirect()->to('profilo')->with('success', 'Profilo aggiornato.');
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
