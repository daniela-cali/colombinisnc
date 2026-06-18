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

        $materialiPost = $this->request->getPost('materiali') ?? [];
        if ($materialiPost) {
            $matModel = new InterventiMaterialiModel();
            foreach ($materialiPost as $m) {
                if (empty($m['articolo_id']) && empty($m['descrizione'])) {
                    continue;
                }
                $matModel->insert(array_merge($m, ['intervento_id' => $id]));
            }
        }

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'operativo/interventi';

        return redirect()->to($dest)->with('success', 'Intervento ' . $codice . ' creato.');
    }

    /**
     * Form modifica intervento con materiali consegnati.
     */
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
     * Form modifica intervento.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $intervento = (new InterventiModel())->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
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
     * Elimina l'intervento (hard delete).
     * Consentito solo se lo stato è "annullato" — impedisce la cancellazione di interventi attivi.
     * I materiali collegati vengono eliminati a cascata dalla FK.
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
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/clienti/' . $clienteId;

        return redirect()->to($dest)->with('success', 'Intervento ' . esc($codice) . ' eliminato.');
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
            'priorita'           => 'required|in_list[' . $prioritaAmmesse . ']',
            'stato'              => 'required|in_list[' . $statiAmmessi . ']',
            'tipo_intervento_id' => 'permit_empty|is_natural_no_zero',
            'data_pianificata'   => 'permit_empty|valid_date[Y-m-d]',
            'data_scadenza'      => 'permit_empty|valid_date[Y-m-d]',
            'durata_stimata'     => 'permit_empty|is_natural',
        ];
    }
}
