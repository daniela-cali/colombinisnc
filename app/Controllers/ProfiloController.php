<?php

namespace App\Controllers;

use App\Controllers\Anagrafiche\PersonaleController;
use App\Libraries\EmailOperatore;
use App\Models\PersonaleCaselleEmailModel;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use RuntimeException;

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
        // La casella serve solo a chi spedisce dal gestionale, cioè a un dipendente.
        $casella = $persona ? (new PersonaleCaselleEmailModel())->perPersonale((int) $persona['id']) : null;

        return view('profilo/index', [
            'persona'             => $persona,
            'user'                => $user,
            'email'               => $user?->getEmailIdentity()?->secret ?? '',
            'pastelli'            => PersonaleController::PASTELLI,
            'colori_usati'        => $persona ? (new PersonaleModel())->coloriUsati($persona['id']) : [],
            'firma'               => $casella['firma_email'] ?? null,
            'ha_password_casella' => ($casella['smtp_password'] ?? null) !== null,
        ]);
    }

    /**
     * Salva la casella email del proprio profilo: la password e la firma, ognuna solo se è
     * arrivata, così si cambia una senza dover reinserire l'altra. Cifratura della password ed
     * estrazione della firma dal file le fa il model a ogni salvataggio.
     */
    public function salvaPosta()
    {
        $persona = (new PersonaleModel())->perUtente(user_id());
        if (! $persona) {
            return redirect()->to('profilo')->with('error', 'Il tuo account non ha una scheda personale.');
        }

        if (! $this->validate([
            'smtp_password' => 'permit_empty|max_length[200]',
            'firma'         => 'ext_in[firma,html,htm]|max_size[firma,2048]',
        ])) {
            return redirect()->to('profilo#posta')->with('errors', $this->validator->getErrors());
        }

        $dati = [];
        if ((string) $this->request->getPost('smtp_password') !== '') {
            $dati['smtp_password'] = $this->request->getPost('smtp_password');
        }
        $file = $this->request->getFile('firma');
        if ($file && $file->isValid()) {
            $dati['firma_email'] = file_get_contents($file->getTempName());
        }

        if ($dati === []) {
            return redirect()->to('profilo#posta')->with('warning', 'Niente da salvare: inserisci la password o scegli il file della firma.');
        }

        (new PersonaleCaselleEmailModel())->salva((int) $persona['id'], $dati);

        return redirect()->to('profilo#posta')->with('success', 'Casella email salvata. Prova a inviarti un\'email di prova.');
    }

    /**
     * Spedisce un'email di prova a sé stessi dalla propria casella: verifica password, server
     * e firma prima di spedire qualcosa a un cliente.
     */
    public function provaPosta()
    {
        try {
            $mittente = new EmailOperatore(user_id());
            $indirizzo = (new UserModel())->findById(user_id())->getEmailIdentity()->secret;

            $mittente->invia(
                $indirizzo,
                'Email di prova dal gestionale',
                "Questa è un'email di prova inviata dal gestionale.\n"
                . "Se la firma e i loghi qui sotto si vedono correttamente, la casella è pronta per inviare le proposte."
            );
        } catch (RuntimeException $e) {
            return redirect()->to('profilo#posta')->with('error', $e->getMessage());
        }

        return redirect()->to('profilo#posta')->with('success', 'Email di prova inviata a ' . $indirizzo . ': controlla che sia arrivata con la firma.');
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
