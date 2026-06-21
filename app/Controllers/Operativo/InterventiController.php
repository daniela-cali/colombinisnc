<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\ArticoliModel;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\PersonaleModel;
use App\Models\TipiInterventoModel;

class InterventiController extends BaseController
{
    /**
     * Lista di tutti gli interventi con cliente e tecnico.
     */
    public function index(): string
    {
        return view('operativo/interventi/index', [
            'interventi'   => (new InterventiModel())->elencoCompleto(),
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'   => InterventiModel::STATI_LABEL,
        ]);
    }

    /**
     * Form nuovo intervento.
     * Se arriva ?cliente_id=X dalla scheda cliente, pre-compila il select.
     */
    public function nuovo(): string
    {
        return view('operativo/interventi/nuovo', [
            'clienti'       => (new ClientiModel())->elencoCompleto(),
            'tecnici'       => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
            'tipi'          => (new TipiInterventoModel())->attivi(),
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'    => InterventiModel::STATI_LABEL,
            'articoliPerCat' => (new ArticoliModel())->perCategoria(),
            'cliente_id'    => (int) $this->request->getGet('cliente_id'),
            'from'          => $this->request->getGet('from') ?? '',
        ]);
    }

    /**
     * Salva il nuovo intervento.
     */
    public function store()
    {
        $model = new InterventiModel();

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id     = $model->insert(array_merge($this->request->getPost(), [
            'codice'  => $model->generaCodice(),
            'urgenza' => (int) (bool) $this->request->getPost('urgenza'),
        ]));
        $codice = $model->find($id)['codice'];

        $matModel      = new InterventiMaterialiModel();
        $materialiPost = $this->request->getPost('materiali') ?? [];
        foreach ($materialiPost as $m) {
            if (empty($m['articolo_id']) && empty($m['descrizione'])) {
                continue;
            }
            $matModel->insert(array_merge($m, [
                'intervento_id' => $id,
                'cliente_id'    => $this->request->getPost('cliente_id'),
            ]));
        }

        // Collega i materiali sospesi selezionati dal dispatcher al nuovo intervento.
        $sospesiIds = array_filter(array_map('intval', $this->request->getPost('sospesi_ids') ?? []));
        foreach ($sospesiIds as $sospId) {
            $matModel->update($sospId, ['intervento_id' => $id]);
        }

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'operativo/interventi';

        return redirect()->to($dest)->with('success', 'Intervento ' . $codice . ' creato.');
    }

    /**
     * Scheda read-only di un intervento.
     */
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $intervento = (new InterventiModel())->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        $cliente  = (new ClientiModel())->find($intervento['cliente_id']);
        $tecnico  = $intervento['tecnico_id'] ? (new PersonaleModel())->find($intervento['tecnico_id']) : null;
        $tipo     = $intervento['tipo_intervento_id'] ? (new TipiInterventoModel())->find($intervento['tipo_intervento_id']) : null;

        return view('operativo/interventi/show', [
            'intervento'   => $intervento,
            'cliente'      => $cliente,
            'tecnico'      => $tecnico,
            'tipo'         => $tipo,
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'   => InterventiModel::STATI_LABEL,
            'materiali'    => (new InterventiMaterialiModel())->perIntervento($id),
        ]);
    }

    /**
     * Chiude l'intervento (stato → completato).
     * Se il POST contiene materiali_consegnati=1, segna tutti i materiali come consegnati.
     */
    public function chiudi(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        // Guardia di sicurezza: la UI nasconde il pulsante in questi stati,
        // ma un POST diretto all'URL bypasserebbe il controllo visivo.
        if (in_array($intervento['stato'], [InterventiModel::STATO_COMPLETATO, InterventiModel::STATO_ANNULLATO])) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'L\'intervento è già chiuso o annullato.');
        }

        $model->update($id, ['stato' => InterventiModel::STATO_COMPLETATO]);

        $materialiConsegnati = $this->request->getPost('materiali_consegnati');
        $matModel = new InterventiMaterialiModel();

        if ($materialiConsegnati === '1') {
            // Tecnico ha consegnato tutto: segna ogni riga come consegnata.
            $matModel->consegnaPerIntervento($id);
        } elseif ($materialiConsegnati === '0') {
            // Materiali non portati: tornano tra i sospesi del cliente
            // con una nota che ricorda da quale intervento provenivano.
            $matModel->liberaPerIntervento($id, $intervento['codice']);
        }
        // Se materialiConsegnati è null l'intervento non aveva materiali: nessuna azione.

        return redirect()->to('operativo/interventi/' . $id)
            ->with('success', 'Intervento ' . esc($intervento['codice']) . ' chiuso.');
    }

    /**
     * Form modifica intervento.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $intervento = (new InterventiModel())->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if ($intervento['stato'] === InterventiModel::STATO_ANNULLATO) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'Un intervento annullato non può essere modificato.');
        }

        $cliente = (new ClientiModel())->find($intervento['cliente_id']);

        return view('operativo/interventi/edit', [
            'intervento'    => $intervento,
            'cliente'       => $cliente,
            'tecnici'       => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
            'tipi'          => (new TipiInterventoModel())->attivi(),
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'    => InterventiModel::STATI_LABEL,
            'materiali'     => (new InterventiMaterialiModel())->perIntervento($id),
            'articoliPerCat' => (new ArticoliModel())->perCategoria(),
            'from'          => $this->request->getGet('from') ?? '',
        ]);
    }

    /**
     * Aggiorna l'intervento.
     */
    public function update(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, array_merge($this->request->getPost(), [
            'urgenza' => (int) (bool) $this->request->getPost('urgenza'),
        ]));

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'operativo/interventi/' . $id . '/edit';

        return redirect()->to($dest)->with('success', 'Intervento aggiornato.');
    }

    /**
     * Annulla l'intervento: stato → annullato e libera i materiali non consegnati verso i sospesi.
     * Unico punto in cui lo stato "annullato" viene impostato: non è più selezionabile dal form di modifica.
     */
    public function annulla(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if (in_array($intervento['stato'], [InterventiModel::STATO_COMPLETATO, InterventiModel::STATO_ANNULLATO])) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'L\'intervento è già chiuso o annullato.');
        }

        $model->update($id, ['stato' => InterventiModel::STATO_ANNULLATO]);
        (new InterventiMaterialiModel())->liberaPerIntervento($id, $intervento['codice']);

        return redirect()->to('operativo/interventi/' . $id)
            ->with('success', 'Intervento ' . esc($intervento['codice']) . ' annullato.');
    }

    /**
     * Elimina l'intervento (hard delete).
     * Consentito solo se lo stato è "annullato" — impedisce la cancellazione di interventi attivi.
     * Guard difensivo: libera i materiali prima del CASCADE per coprire record storici impostati
     * ad "annullato" prima dell'introduzione del bottone dedicato (quando era possibile farlo
     * dal dropdown del form). Sul DB di produzione (svuotato al go-live) questo non servirà.
     */
    public function delete(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if ($intervento['stato'] !== InterventiModel::STATO_ANNULLATO) {
            return redirect()->to('operativo/interventi/' . $id . '/edit')
                ->with('error', 'L\'intervento può essere eliminato solo se è nello stato "Annullato".');
        }

        $codice    = $intervento['codice'];
        $clienteId = $intervento['cliente_id'];

        // Libera i materiali prima del CASCADE: tornano sospesi con nota dell'intervento di origine.
        (new InterventiMaterialiModel())->liberaPerIntervento($id, $codice);
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/clienti/' . $clienteId;

        return redirect()->to($dest)->with('success', 'Intervento ' . esc($codice) . ' eliminato.');
    }

    /**
     * Pianifica l'intervento: imposta data_pianificata, tecnico_id facoltativo e stato → pianificato.
     * Chiamato via AJAX dal drag-and-drop sul calendario.
     */
    public function pianifica(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento || $intervento['stato'] !== InterventiModel::STATO_DA_PIANIFICARE) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Intervento non pianificabile.']);
        }

        $dataPian = $this->request->getPost('data_pianificata');
        if (! $dataPian) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Data mancante.']);
        }

        $dati = [
            'data_pianificata' => date('Y-m-d H:i:s', strtotime($dataPian)),
            'stato'            => InterventiModel::STATO_PIANIFICATO,
        ];
        $tecnicoId = (int) $this->request->getPost('tecnico_id');
        if ($tecnicoId) {
            $dati['tecnico_id'] = $tecnicoId;
        }

        $model->update($id, $dati);

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    /**
     * Rimuove la pianificazione: azzera data_pianificata e tecnico_id, stato → da_pianificare.
     * Chiamato via AJAX dal bottone × sugli eventi del calendario.
     */
    public function annullaPianificazione(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Intervento non trovato.']);
        }

        if (! in_array($intervento['stato'], [InterventiModel::STATO_PIANIFICATO, InterventiModel::STATO_IN_CORSO])) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Impossibile rimuovere la pianificazione in questo stato.']);
        }

        $model->update($id, [
            'data_pianificata' => null,
            'tecnico_id'       => null,
            'stato'            => InterventiModel::STATO_DA_PIANIFICARE,
        ]);

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    // -------------------------------------------------------------------------

    /**
     * Regole di validazione comuni per insert e update.
     * I valori ammessi per tipo e stato vengono letti dalle costanti del model:
     * aggiungere un valore richiede solo modificare InterventiModel.
     */
    private function regolaValidazione(): array
    {
        $prioritaAmmesse = implode(',', array_keys(InterventiModel::PRIORITA_LABEL));
        $statiAmmessi    = implode(',', array_keys(InterventiModel::STATI_LABEL));

        return [
            'cliente_id'         => 'required|is_natural_no_zero',
            'descrizione'        => 'required|min_length[3]',
            'priorita'           => 'required|in_list[' . $prioritaAmmesse . ']',
            'stato'              => 'required|in_list[' . $statiAmmessi . ']',
            'tipo_intervento_id' => 'required|is_natural_no_zero',
            'data_pianificata'   => 'permit_empty|valid_date[Y-m-d\TH:i]',
            'data_scadenza'      => 'permit_empty|valid_date[Y-m-d]',
            'durata_stimata'     => 'permit_empty|is_natural',
        ];
    }
}
