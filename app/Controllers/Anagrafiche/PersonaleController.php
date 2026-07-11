<?php

namespace App\Controllers\Anagrafiche;

use App\Controllers\BaseController;
use App\Models\AssenzeModel;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Entities\User;

class PersonaleController extends BaseController
{
    private array $gruppi = [
        'admin'     => 'Amministratore',
        'ufficio'   => 'Ufficio',
        'developer' => 'Sviluppatore',
        'tecnico'   => 'Tecnico',
    ];

    // Palette colore profilo: tinte distinte per identificare il dipendente nel calendario
    private const PASTELLI = [
        '#e5645a', '#ef8b5a', '#f2b04a', '#e8ce4d', '#b5d24f',
        '#78c15c', '#4fb389', '#4cb6c4', '#4f9fe0', '#5b7ce0',
        '#7d6fd6', '#a469d3', '#c96ec4', '#e46a9b', '#8b98a8',
        '#3a4149',
    ];

    /**
     * Lista tutto il personale con account e gruppi Shield.
     */
    public function index(): string
    {
        return view('anagrafiche/personale/index', [
            'personale'    => (new PersonaleModel())->elencoCompleto(),
            'help_sezione' => 'personale',
        ]);
    }

    /**
     * Scheda dipendente in sola lettura: anagrafica, account e gruppi.
     */
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        helper('colore');

        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        $user  = $persona['user_id'] ? (new UserModel())->findById($persona['user_id']) : null;
        $email = $user?->getEmailIdentity()?->secret ?? '';

        return view('anagrafiche/personale/show', [
            'persona'           => $persona,
            'user'              => $user,
            'email'             => $email,
            'gruppi'            => $this->gruppi,
            'assenze'           => (new AssenzeModel())->perPersonale($id),
            'tipiAssenzaLabel'  => AssenzeModel::TIPI_LABEL,
            'tipiAssenzaBadge'  => AssenzeModel::TIPI_BADGE,
            'puoGestireAssenze' => auth()->user()->inGroup('ufficio', 'admin', 'developer'),
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

    /**
     * Aggiunge un'assenza al diario del dipendente, tornando alla pagina di origine.
     * Se si sovrappone a un'assenza già registrata per lo stesso dipendente, il salvataggio
     * procede comunque: viene solo segnalato con un avviso (non è un blocco).
     */
    public function aggiungiAssenza(): RedirectResponse
    {
        if ($r = $this->soloGestoriAssenze()) {
            return $r;
        }

        if (! $this->validate([
            'personale_id' => 'required|is_natural_no_zero',
            'tipo'         => 'required|in_list[ferie,malattia,permesso,altro]',
            'data_inizio'  => 'required|valid_date[Y-m-d]',
            'data_fine'    => 'required|valid_date[Y-m-d]',
            'note'         => 'permit_empty',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $personaleId = (int) $this->request->getPost('personale_id');
        $dataInizio  = $this->request->getPost('data_inizio');
        $dataFine    = $this->request->getPost('data_fine');

        if ($dataFine < $dataInizio) {
            return redirect()->back()->with('error', 'La data di fine non può precedere la data di inizio.');
        }

        $model = new AssenzeModel();
        $model->insert($this->request->getPost());

        $sovrapposte = $model->sovrapposizioni($personaleId, $dataInizio, $dataFine, (int) $model->getInsertID());

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/personale/' . $personaleId . '#sec-assenze';

        if ($sovrapposte) {
            return redirect()->to($dest)->with('warning', 'Assenza aggiunta, ma si sovrappone a un\'altra assenza già registrata per questo dipendente.');
        }

        return redirect()->to($dest)->with('success', 'Assenza aggiunta.');
    }

    /**
     * Elimina un'assenza dal diario, tornando alla scheda del dipendente di origine.
     */
    public function eliminaAssenza(int $id): RedirectResponse
    {
        if ($r = $this->soloGestoriAssenze()) {
            return $r;
        }

        $model   = new AssenzeModel();
        $assenza = $model->find($id);
        if (! $assenza) {
            return redirect()->back()->with('error', 'Assenza non trovata.');
        }

        $personaleId = (int) $assenza['personale_id'];
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/personale/' . $personaleId . '#sec-assenze';

        return redirect()->to($dest)->with('success', 'Assenza eliminata.');
    }

    /**
     * Consente la gestione delle assenze solo a ufficio e amministratori.
     * Restituisce un RedirectResponse se l'utente non è autorizzato, altrimenti null.
     */
    private function soloGestoriAssenze(): ?RedirectResponse
    {
        if (! auth()->user()->inGroup('ufficio', 'admin', 'developer')) {
            return redirect()->back()->with('error', 'Non hai i permessi per gestire le assenze.');
        }

        return null;
    }
}
