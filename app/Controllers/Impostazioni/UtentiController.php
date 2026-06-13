<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UtentiController extends BaseController
{
    private array $gruppiApp = [
        'admin'     => 'Amministratore',
        'developer' => 'Sviluppatore',
        'ufficio'   => 'Ufficio',
        'tecnico'   => 'Tecnico',
        'cliente'   => 'Cliente',
    ];

    /**
     * Lista di tutti gli utenti Shield con gruppi e nominativo da personale.
     */
    public function utentiApp(): string
    {
        return view('impostazioni/utenti_app', [
            'utenti' => (new UserModel())->tuttiConGruppi(),
        ]);
    }

    /**
     * Form modifica utente: email, gruppi e password opzionale.
     * Nome/cognome si gestiscono in Anagrafiche > Personale.
     */
    public function editUtenteApp(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $user = (new UserModel())->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        return view('impostazioni/edit_utente_app', [
            'utente'          => $user,
            'email'           => $user->getEmailIdentity()?->secret ?? '',
            'gruppi'          => $this->gruppiApp,
            'gruppi_correnti' => $user->getGroups(),
        ]);
    }

    /**
     * Aggiorna email, gruppi (multipli) e password opzionale.
     */
    public function updateUtenteApp(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $users = new UserModel();
        $user  = $users->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        $rules    = ['email' => 'required|valid_email|max_length[254]'];
        $password = $this->request->getPost('password');
        if ($password) {
            $rules['password']         = 'min_length[8]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (! $this->validate($rules, ['password_confirm' => ['matches' => 'Le password non coincidono.']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $identity   = $user->getEmailIdentity();
        $nuovaEmail = $this->request->getPost('email');
        if ($identity && $identity->secret !== $nuovaEmail) {
            $identity->secret = $nuovaEmail;
            model(\CodeIgniter\Shield\Models\UserIdentityModel::class)->save($identity);
        }

        $gruppiNuovi    = (array) $this->request->getPost('gruppi');
        $gruppiCorrenti = $user->getGroups();
        foreach (array_diff($gruppiCorrenti, $gruppiNuovi) as $g) {
            $user->removeGroup($g);
        }
        foreach (array_diff($gruppiNuovi, $gruppiCorrenti) as $g) {
            if (isset($this->gruppiApp[$g])) {
                $user->addGroup($g);
            }
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
     * Elimina utente Shield (hard delete). Il record personale rimane con user_id = NULL.
     */
    public function deleteUtenteApp(int $id): \CodeIgniter\HTTP\RedirectResponse
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
