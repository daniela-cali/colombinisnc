<?php

namespace App\Controllers\Anagrafiche;

use App\Controllers\BaseController;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class PersonaleController extends BaseController
{
    private array $gruppi = [
        'ufficio'   => 'Ufficio',
        'tecnico'   => 'Tecnico',
        'admin'     => 'Amministratore',
        'developer' => 'Sviluppatore',
    ];

    // Scorciatoie colore nel picker profilo (S=75%, L=73% per coerenza con lo slider)
    private const PASTELLI = [
        '#ee8686', '#eeab86', '#d4a574', '#eec886', '#eee586',
        '#cfee86', '#98ee86', '#86eebf', 
        '#86e5ee', '#86b5ee', '#9386ee', '#d986ee', '#ee86b5',
    ];

    /**
     * Lista tutto il personale con account e gruppi Shield.
     */
    public function index(): string
    {
        return view('anagrafiche/personale/index', [
            'personale' => (new PersonaleModel())->elencoCompleto(),
        ]);
    }

    /**
     * Form creazione nuovo dipendente con account Shield.
     */
    public function nuovo(): string
    {
        return view('anagrafiche/personale/nuovo', [
            'gruppi'       => $this->gruppi,
            'pastelli'     => self::PASTELLI,
            'colori_usati' => (new PersonaleModel())->coloriUsati(),
        ]);
    }

    /**
     * Salva il nuovo dipendente: crea account Shield e record personale.
     */
    public function store()
    {
        $rules = [
            'nome'             => 'required|max_length[100]',
            'cognome'          => 'required|max_length[100]',
            'telefono'         => 'permit_empty|max_length[20]',
            'colore'           => 'permit_empty|max_length[7]',
            'username'         => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'            => 'required|valid_email|max_length[254]',
            'gruppi'           => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'username'         => ['is_unique' => 'Questo nome utente è già in uso.'],
            'gruppi'           => ['required'  => 'Seleziona almeno un gruppo.'],
            'password_confirm' => ['matches'   => 'Le password non coincidono.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = new User([
            'username' => $this->request->getPost('username'),
            'active'   => true,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());

        $user->createEmailIdentity([
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        foreach ((array) $this->request->getPost('gruppi') as $gruppo) {
            if (isset($this->gruppi[$gruppo])) {
                $user->addGroup($gruppo);
            }
        }

        (new PersonaleModel())->insert([
            'user_id'  => $user->id,
            'nome'     => $this->request->getPost('nome'),
            'cognome'  => $this->request->getPost('cognome'),
            'telefono' => $this->request->getPost('telefono') ?: null,
            'colore'   => $this->request->getPost('colore') ?: null,
        ]);

        return redirect()->to('anagrafiche/personale')
            ->with('success', esc($this->request->getPost('cognome') . ' ' . $this->request->getPost('nome')) . ' aggiunto con successo.');
    }

    /**
     * Form modifica dipendente con dati anagrafica e gruppi correnti.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        $user          = $persona['user_id'] ? (new UserModel())->findById($persona['user_id']) : null;
        $gruppiCorrenti = $user ? $user->getGroups() : [];
        $email          = $user?->getEmailIdentity()?->secret ?? '';

        return view('anagrafiche/personale/edit', [
            'persona'         => $persona,
            'user'            => $user,
            'email'           => $email,
            'gruppi'          => $this->gruppi,
            'gruppi_correnti' => $gruppiCorrenti,
            'pastelli'        => self::PASTELLI,
            'colori_usati'    => (new PersonaleModel())->coloriUsati($id),
        ]);
    }

    /**
     * Aggiorna anagrafica, email, gruppi e password opzionale del dipendente.
     */
    public function update(int $id)
    {
        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        $rules = [
            'nome'     => 'required|max_length[100]',
            'cognome'  => 'required|max_length[100]',
            'telefono' => 'permit_empty|max_length[20]',
            'colore'   => 'permit_empty|max_length[7]',
            'gruppi'   => 'required',
        ];

        if ($persona['user_id']) {
            $rules['email'] = 'required|valid_email|max_length[254]';
            $password = $this->request->getPost('password');
            if ($password) {
                $rules['password']         = 'min_length[8]';
                $rules['password_confirm'] = 'matches[password]';
            }
        }

        if (! $this->validate($rules, [
            'gruppi'           => ['required' => 'Seleziona almeno un gruppo.'],
            'password_confirm' => ['matches'  => 'Le password non coincidono.'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new PersonaleModel())->update($id, [
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

            $gruppiNuovi    = (array) $this->request->getPost('gruppi');
            $gruppiCorrenti = $user->getGroups();

            foreach (array_diff($gruppiCorrenti, $gruppiNuovi) as $g) {
                $user->removeGroup($g);
            }
            foreach (array_diff($gruppiNuovi, $gruppiCorrenti) as $g) {
                if (isset($this->gruppi[$g])) {
                    $user->addGroup($g);
                }
            }

            if (! empty($password)) {
                $user = $users->findById($persona['user_id']);
                $user->setPassword($password);
                $users->save($user);
            }
        }

        return redirect()->to('anagrafiche/personale')
            ->with('success', 'Dipendente aggiornato.');
    }

    /**
     * Elimina il dipendente e il relativo account Shield (hard delete).
     * Da implementare: verificare assenza di interventi collegati prima di eliminare.
     */
    public function delete(int $id)
    {
        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        if ($persona['user_id']) {
            (new UserModel())->delete($persona['user_id'], true);
        }

        (new PersonaleModel())->delete($id);

        return redirect()->to('anagrafiche/personale')
            ->with('success', esc($persona['cognome'] . ' ' . $persona['nome']) . ' eliminato.');
    }
}
