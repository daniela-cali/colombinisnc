<?php

namespace App\Controllers\Anagrafiche;

use App\Controllers\BaseController;
use App\Models\AssenzeModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class PersonaleController extends BaseController
{
    private array $gruppi = [
        'admin'     => 'Amministratore',
        'ufficio'   => 'Ufficio',
        'developer' => 'Sviluppatore',
        'tecnico'   => 'Tecnico',
    ];

    // Palette colore profilo: tinte distinte per identificare il dipendente nel calendario
    public const PASTELLI = [
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
            'puoCreare'    => auth()->user()->can('personale.account'),
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
            'email'            => 'required|valid_email|max_length[254]|is_unique[auth_identities.secret]',
            'gruppi'           => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'username'         => ['is_unique' => 'Questo nome utente è già in uso.'],
            'email'            => ['is_unique' => 'Questa email è già associata a un altro account.'],
            'gruppi'           => ['required'  => 'Seleziona almeno un gruppo.'],
            'password_confirm' => ['matches'   => 'Le password non coincidono.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Account e scheda nella stessa transazione: sono una cosa sola per chi la usa,
        // e un account creato a metà occuperebbe lo username senza permettere di rientrare.
        $db = db_connect();
        $db->transStart();

        $user = (new UserModel())->creaAccount($this->request->getPost(), array_keys($this->gruppi));

        (new PersonaleModel())->insert([
            'user_id'  => $user->id,
            'nome'     => $this->request->getPost('nome'),
            'cognome'  => $this->request->getPost('cognome'),
            'telefono' => $this->request->getPost('telefono') ?: null,
            'colore'   => $this->request->getPost('colore') ?: null,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Creazione non riuscita: nessun dato è stato salvato. Riprova.');
        }

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
            'persona'           => $persona,
            'user'              => $user,
            'email'             => $email,
            'puoGestireAccount' => auth()->user()->can('personale.account'),
            'puoEliminare'      => auth()->user()->can('personale.elimina'),
            'gruppi'            => $this->gruppi,
            'gruppi_correnti'   => $gruppiCorrenti,
            'pastelli'          => self::PASTELLI,
            'colori_usati'      => (new PersonaleModel())->coloriUsati($id),
        ]);
    }

    /**
     * Aggiorna anagrafica del dipendente e, per chi ha personale.account,
     * anche email, ruoli e password del suo account.
     *
     * Chi non ha quel permesso (l'ufficio) non vede il blocco account nel form e non lo
     * tocca nemmeno inviando i campi a mano: il controller non passa proprio dal model.
     */
    public function update(int $id)
    {
        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        $gestisceAccount = $persona['user_id'] && auth()->user()->can('personale.account');

        $rules = [
            'nome'     => 'required|max_length[100]',
            'cognome'  => 'required|max_length[100]',
            'telefono' => 'permit_empty|max_length[20]',
            'colore'   => 'permit_empty|max_length[7]',
        ];

        if ($gestisceAccount) {
            // L'unicità ignora le identità dell'utente stesso, altrimenti risalvare il
            // form senza cambiare l'email fallirebbe contro sé stessa.
            $rules['email']  = 'required|valid_email|max_length[254]|is_unique[auth_identities.secret,user_id,' . $persona['user_id'] . ']';
            $rules['gruppi'] = 'required';

            $password = $this->request->getPost('password');
            if ($password) {
                $rules['password']         = 'min_length[8]';
                $rules['password_confirm'] = 'matches[password]';
            }
        }

        if (! $this->validate($rules, [
            'email'            => ['is_unique' => 'Questa email è già associata a un altro account.'],
            'gruppi'           => ['required'  => 'Seleziona almeno un gruppo.'],
            'password_confirm' => ['matches'   => 'Le password non coincidono.'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($gestisceAccount && ($r = $this->bloccaAutoDeclassamento((int) $persona['user_id']))) {
            return $r;
        }

        (new PersonaleModel())->update($id, [
            'nome'     => $this->request->getPost('nome'),
            'cognome'  => $this->request->getPost('cognome'),
            'telefono' => $this->request->getPost('telefono') ?: null,
            'colore'   => $this->request->getPost('colore') ?: null,
        ]);

        if ($gestisceAccount) {
            $users = new UserModel();
            $users->aggiornaAccount(
                $users->findById($persona['user_id']),
                $this->request->getPost(),
                array_keys($this->gruppi)
            );
        }

        return redirect()->to('anagrafiche/personale')
            ->with('success', 'Dipendente aggiornato.');
    }

    /**
     * Impedisce di togliersi da soli ogni ruolo amministrativo, restando fuori dalle
     * sezioni che si stavano usando: è un errore da cui si esce solo dal database.
     *
     * Vale solo su sé stessi e solo se si perdono entrambi i ruoli: passare da admin a
     * developer, o togliere admin a un collega, restano operazioni legittime.
     *
     * @return RedirectResponse|null il redirect di rifiuto, oppure null se si può procedere
     */
    private function bloccaAutoDeclassamento(int $utenteModificato): ?RedirectResponse
    {
        if ($utenteModificato !== user_id()) {
            return null;
        }

        $amministrativi = ['admin', 'developer'];
        $richiesti      = (array) $this->request->getPost('gruppi');

        if (array_intersect(auth()->user()->getGroups(), $amministrativi)
            && ! array_intersect($richiesti, $amministrativi)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Non puoi rimuovere a te stesso il ruolo di amministratore: chiedi a un altro amministratore di farlo.'
            );
        }

        return null;
    }

    /**
     * Elimina il dipendente e il relativo account Shield (hard delete).
     *
     * Consentito solo a chi non ha lasciato tracce, cioè in pratica ai record inseriti
     * per errore: interventi.tecnico_id ha ON DELETE SET NULL e assenze.personale_id ha
     * ON DELETE CASCADE, quindi eliminare chi ha lavorato azzererebbe il tecnico su tutto
     * il suo storico e cancellerebbe il suo diario delle assenze, senza avvisare nessuno.
     * Per escludere dal gestionale una persona che ha lavorato si sospende l'accesso.
     */
    public function delete(int $id)
    {
        $persona = (new PersonaleModel())->find($id);

        if (! $persona) {
            return redirect()->to('anagrafiche/personale')->with('error', 'Dipendente non trovato.');
        }

        // Elimina anche l'account Shield: senza questo controllo ci si cancellerebbe
        // l'accesso mentre lo si sta usando, e si rientrerebbe solo dal database.
        if ((int) $persona['user_id'] === user_id()) {
            return redirect()->to('anagrafiche/personale')
                ->with('error', 'Non puoi eliminare la tua scheda: cancelleresti l\'account con cui stai lavorando.');
        }

        $interventi = (new InterventiModel())->contaPerTecnico($id);
        $assenze    = (new AssenzeModel())->contaPerPersonale($id);

        if ($interventi > 0 || $assenze > 0) {
            $tracce = [];
            if ($interventi > 0) {
                $tracce[] = $interventi . ' intervent' . ($interventi === 1 ? 'o' : 'i');
            }
            if ($assenze > 0) {
                $tracce[] = $assenze . ' assenz' . ($assenze === 1 ? 'a' : 'e');
            }

            return redirect()->to('anagrafiche/personale')->with(
                'error',
                esc($persona['cognome'] . ' ' . $persona['nome']) . ' ha ' . implode(' e ', $tracce)
                . ' in archivio e non può essere eliminato: si perderebbe lo storico. Per togliergli l\'accesso, sospendi il suo account da Impostazioni → Utenti.'
            );
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
     * Se si sovrappone a un'assenza già registrata, o copre interventi già pianificati
     * su questo dipendente, il salvataggio procede comunque: viene solo segnalato con
     * un avviso (non è un blocco) — la riassegnazione resta un passo manuale successivo.
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
        $inConflitto = model(InterventiModel::class)->agendaTecnicoPeriodo($personaleId, $dataInizio, $dataFine);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/personale/' . $personaleId . '#sec-assenze';

        $avvisi = [];
        if ($sovrapposte) {
            $avvisi[] = 'si sovrappone a un\'altra assenza già registrata per questo dipendente';
        }
        if ($inConflitto) {
            $elenco = implode(', ', array_map(
                static fn (array $i) => esc($i['cliente_denominazione']) . ' (' . date('d/m/Y', strtotime($i['data_pianificata'])) . ')',
                $inConflitto
            ));
            $avvisi[] = 'risultano già pianificati interventi su questo dipendente in quel periodo: ' . $elenco;
        }

        if ($avvisi) {
            return redirect()->to($dest)->with('warning', 'Assenza aggiunta, ma ' . implode('; inoltre ', $avvisi) . '.');
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
