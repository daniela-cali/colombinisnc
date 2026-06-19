<?php

namespace App\Controllers\Anagrafiche;

use App\Controllers\BaseController;
use App\Models\ArticoliModel;
use App\Models\ClientiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;

class ClientiController extends BaseController
{
    /**
     * Lista clienti con denominazione calcolata e tecnico preferito.
     */
    public function index(): string
    {
        return view('anagrafiche/clienti/index', [
            'clienti' => (new ClientiModel())->elencoCompleto(),
        ]);
    }

    /**
     * Scheda cliente in sola lettura: layout verticale scrollabile.
     */
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $matModel = new InterventiMaterialiModel();

        return view('anagrafiche/clienti/show', [
            'cliente'        => $cliente,
            'interventi'     => (new InterventiModel())->perCliente($id),
            'sospesi'        => $matModel->sospesiPerCliente($id),
            'articoliPerCat' => (new ArticoliModel())->perCategoria(),
            'prioritaLabel'  => InterventiModel::PRIORITA_LABEL,
            'statiLabel'     => InterventiModel::STATI_LABEL,
        ]);
    }

    /**
     * Storico materiali del cliente: sospesi + per intervento, raggruppati via PHP.
     */
    public function materiali(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $matModel = new InterventiMaterialiModel();

        return view('anagrafiche/clienti/materiali', [
            'cliente'   => $cliente,
            'sospesi'   => $matModel->sospesiPerCliente($id),
            'materiali' => $matModel->perCliente($id),
            'statiLabel' => InterventiModel::STATI_LABEL,
        ]);
    }

    /**
     * Form creazione nuovo cliente.
     */
    public function nuovo(): string
    {
        return view('anagrafiche/clienti/nuovo', [
            'tecnici' => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
        ]);
    }

    /**
     * Salva il nuovo cliente.
     * La geocodifica avviene lato JS: lat, lng, geocoded_at e geocodifica_fallita
     * arrivano già compilati dal form.
     */
    public function store()
    {
        $model = new ClientiModel();
        $tipo  = $this->request->getPost('tipo');

        $rules = $this->regolaValidazione($tipo);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->insert(array_merge($this->request->getPost(), [
            'codice' => $model->generaCodice(),
            'attivo' => 1,
        ]));

        return redirect()->to('anagrafiche/clienti')
            ->with('success', esc($this->denominazioneDaPost()) . ' aggiunto/a con successo.');
    }

    /**
     * Form modifica cliente con tab Anagrafica / Interventi / Materiali.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        return view('anagrafiche/clienti/edit', [
            'cliente' => $cliente,
            'tecnici' => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
        ]);
    }

    /**
     * Aggiorna il cliente.
     * Se l'utente ha rieseguito la geocodifica dalla scheda, lat/lng/geocoded_at
     * arrivano aggiornati nel POST; altrimenti rimangono i valori pre-caricati nel form.
     */
    public function update(int $id)
    {
        $model   = new ClientiModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $tipo  = $this->request->getPost('tipo');
        $rules = $this->regolaValidazione($tipo);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        return redirect()->to('anagrafiche/clienti/' . $id . '/edit')
            ->with('success', 'Cliente aggiornato.');
    }

    /**
     * Elimina il cliente (hard delete).
     * Da v0.8.0: aggiungere controllo su interventi collegati prima di procedere.
     */
    public function delete(int $id)
    {
        $model   = new ClientiModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $denom = ClientiModel::denominazione($cliente);
        $model->delete($id);

        return redirect()->to('anagrafiche/clienti')
            ->with('success', esc($denom) . ' eliminato/a.');
    }

    // -------------------------------------------------------------------------

    /**
     * Regole di validazione comuni + quelle condizionali per tipo cliente.
     */
    private function regolaValidazione(string $tipo): array
    {
        $rules = [
            'tipo'     => 'required|in_list[societa,persona_fisica]',
            'telefono' => 'permit_empty|max_length[50]',
            'email'    => 'permit_empty|valid_email|max_length[255]',
            'piva'     => 'permit_empty|max_length[15]',
            'cfisc'    => 'permit_empty|max_length[16]',
            'cap'      => 'permit_empty|max_length[10]',
            'zona'     => 'permit_empty|in_list[-1,0,1]',
        ];

        if ($tipo === ClientiModel::TIPO_SOCIETA) {
            $rules['ragsoc'] = 'required|max_length[255]';
        } else {
            $rules['nome']    = 'required|max_length[100]';
            $rules['cognome'] = 'required|max_length[100]';
        }

        return $rules;
    }

    /**
     * Estrae la denominazione leggibile dai dati POST per i messaggi flash.
     */
    private function denominazioneDaPost(): string
    {
        if ($this->request->getPost('tipo') === ClientiModel::TIPO_SOCIETA) {
            return $this->request->getPost('ragsoc') ?? '';
        }

        return trim(
            ($this->request->getPost('cognome') ?? '') . ' ' .
            ($this->request->getPost('nome') ?? '')
        );
    }
}
