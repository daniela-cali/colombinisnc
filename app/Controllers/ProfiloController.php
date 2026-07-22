<?php

namespace App\Controllers;

use App\Controllers\Anagrafiche\PersonaleController;
use App\Models\PersonaleModel;
use App\Models\UserModel;

class ProfiloController extends BaseController
{
    /**
     * Form di modifica del proprio profilo: solo anagrafica ed account,
     * niente gruppi (a differenza del form admin di Anagrafiche\PersonaleController).
     */
    public function index()
    {
        $persona = (new PersonaleModel())->perUtente(user_id());

        if (! $persona) {
            return redirect()->to('/')->with('error', 'Nessuna scheda personale associata al tuo account.');
        }

        $user  = $persona['user_id'] ? (new UserModel())->findById($persona['user_id']) : null;
        $email = $user?->getEmailIdentity()?->secret ?? '';

        return view('profilo/index', [
            'persona'      => $persona,
            'user'         => $user,
            'email'        => $email,
            'pastelli'     => PersonaleController::PASTELLI,
            'colori_usati' => (new PersonaleModel())->coloriUsati($persona['id']),
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

        if (! $persona) {
            return redirect()->to('/')->with('error', 'Nessuna scheda personale associata al tuo account.');
        }

        $rules = [
            'nome'     => 'required|max_length[100]',
            'cognome'  => 'required|max_length[100]',
            'telefono' => 'permit_empty|max_length[20]',
            'colore'   => 'permit_empty|max_length[7]',
        ];

        if ($persona['user_id']) {
            $rules['email'] = 'required|valid_email|max_length[254]';
            $password        = $this->request->getPost('password');
            if ($password) {
                $rules['password']         = 'min_length[8]';
                $rules['password_confirm'] = 'matches[password]';
            }
        }

        if (! $this->validate($rules, [
            'password_confirm' => ['matches' => 'Le password non coincidono.'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new PersonaleModel())->update($persona['id'], [
            'nome'     => $this->request->getPost('nome'),
            'cognome'  => $this->request->getPost('cognome'),
            'telefono' => $this->request->getPost('telefono') ?: null,
            'colore'   => $this->request->getPost('colore') ?: null,
        ]);

        if ($persona['user_id']) {
            $users = new UserModel();
            $user  = $users->findById($persona['user_id']);

            $identity   = $user->getEmailIdentity();
            $nuovaEmail = $this->request->getPost('email');
            if ($identity && $identity->secret !== $nuovaEmail) {
                $identity->secret = $nuovaEmail;
                model(\CodeIgniter\Shield\Models\UserIdentityModel::class)->save($identity);
            }

            if (! empty($password)) {
                $user->setPassword($password);
                $users->save($user);
            }
        }

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
