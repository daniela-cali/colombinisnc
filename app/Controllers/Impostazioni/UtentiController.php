<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class UtentiController extends BaseController
{
    private array $gruppiApp = ['admin' => 'Amministratore', 'staff' => 'Staff', 'tecnici' => 'Tecnico'];

    /**
     * Lista utenti app (admin, staff, tecnici).
     */
    public function utentiApp(): string
    {
        $ids = db_connect()
            ->table('auth_groups_users')
            ->whereIn('group', array_keys($this->gruppiApp))
            ->select('user_id')
            ->get()->getResultArray();

        $utenti = [];
        if ($ids) {
            $utenti = (new UserModel())
                ->whereIn('id', array_column($ids, 'user_id'))
                ->orderBy('cognome')
                ->orderBy('nome')
                ->findAll();
        }

        return view('impostazioni/utenti_app', [
            'utenti' => $utenti,
        ]);
    }

    /**
     * Form creazione nuovo utente app.
     */
    public function creaUtenteApp(): string
    {
        return view('impostazioni/crea_utente_app', [
            'gruppi' => $this->gruppiApp,
        ]);
    }

    /**
     * Salva nuovo utente app con gruppo Shield.
     */
    public function storeUtenteApp()
    {
        $rules = [
            'username'         => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'nome'             => 'required|max_length[100]',
            'cognome'          => 'required|max_length[100]',
            'email'            => 'required|valid_email|max_length[254]',
            'gruppo'           => 'required|in_list[' . implode(',', array_keys($this->gruppiApp)) . ']',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'username'         => ['is_unique' => 'Questo nome utente è già in uso.'],
            'password_confirm' => ['matches'   => 'Le password non coincidono.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = new User([
            'username' => $this->request->getPost('username'),
            'nome'     => $this->request->getPost('nome'),
            'cognome'  => $this->request->getPost('cognome'),
            'active'   => true,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());

        $user->createEmailIdentity([
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        $user->addGroup($this->request->getPost('gruppo'));

        return redirect()->to('impostazioni/utenti-app')
            ->with('success', 'Utente "' . $user->username . '" creato con successo.');
    }

    /**
     * Form modifica utente app.
     */
    public function editUtenteApp(int $id)
    {
        $user = (new UserModel())->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        return view('impostazioni/edit_utente_app', [
            'utente'          => $user,
            'gruppi'          => $this->gruppiApp,
            'gruppo_corrente' => $user->getGroups()[0] ?? '',
        ]);
    }

    /**
     * Aggiorna dati utente app, gruppo e password opzionale.
     */
    public function updateUtenteApp(int $id)
    {
        $users = new UserModel();
        $user  = $users->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        $rules = [
            'nome'    => 'required|max_length[100]',
            'cognome' => 'required|max_length[100]',
            'email'   => 'required|valid_email|max_length[254]',
            'gruppo'  => 'required|in_list[' . implode(',', array_keys($this->gruppiApp)) . ']',
        ];

        $password = $this->request->getPost('password');
        if ($password) {
            $rules['password']         = 'min_length[8]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (! $this->validate($rules, ['password_confirm' => ['matches' => 'Le password non coincidono.']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users->update($id, [
            'nome'    => $this->request->getPost('nome'),
            'cognome' => $this->request->getPost('cognome'),
        ]);

        $identity   = $user->getEmailIdentity();
        $nuovaEmail = $this->request->getPost('email');
        if ($identity && $identity->secret !== $nuovaEmail) {
            $identity->secret = $nuovaEmail;
            model(\CodeIgniter\Shield\Models\UserIdentityModel::class)->save($identity);
        }

        $nuovoGruppo    = $this->request->getPost('gruppo');
        $gruppoCorrente = $user->getGroups()[0] ?? null;
        if ($gruppoCorrente !== $nuovoGruppo) {
            if ($gruppoCorrente) {
                $user->removeGroup($gruppoCorrente);
            }
            $user->addGroup($nuovoGruppo);
        }

        if ($password) {
            $user = $users->findById($id);
            $user->setPassword($password);
            $users->save($user);
        }

        return redirect()->to('impostazioni/utenti-app')
            ->with('success', 'Utente "' . $user->username . '" aggiornato.');
    }

    /**
     * Elimina utente app (hard delete).
     */
    public function deleteUtenteApp(int $id)
    {
        $users = new UserModel();
        $user  = $users->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        $users->delete($id, true);

        return redirect()->to('impostazioni/utenti-app')
            ->with('success', 'Utente "' . $user->username . '" eliminato.');
    }
}
