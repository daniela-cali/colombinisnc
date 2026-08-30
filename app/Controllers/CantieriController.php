<?php

namespace App\Controllers;

use App\Models\CantieriModel;
use App\Models\CantieriNoteModel;
use App\Models\ClientiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\TipiInterventoModel;
use CodeIgniter\HTTP\RedirectResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class CantieriController extends BaseController
{
    /**
     * Index globale: tutti i cantieri con denominazione cliente e stato.
     */
    public function index(): string
    {
        return view('cantieri/index', [
            'title'        => 'Cantieri',
            'cantieri'     => (new CantieriModel())->elencoCompleto(),
            'tipiLabel'    => CantieriModel::TIPI_LABEL,
            'statiLabel'   => CantieriModel::STATI_LABEL,
            'statiBadge'   => CantieriModel::STATI_BADGE,
            'help_sezione' => 'cantieri',
        ]);
    }

    /**
     * Form nuovo cantiere.
     * Accetta ?cliente_id=N per pre-compilare il cliente (link da scheda cliente)
     * e ?from=URL per tornare alla pagina di origine dopo il salvataggio.
     */
    public function nuovo(): string
    {
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);

        return view('cantieri/nuovo', [
            'title'      => 'Nuovo cantiere',
            'cantiere'   => null,
            'cliente'    => $clienteId ? (new ClientiModel())->find($clienteId) : null,
            'clienti'    => (new ClientiModel())->orderBy('ragsoc')->findAll(),
            'tipi'       => (new TipiInterventoModel())->attivi(),
            'tipiLabel'  => CantieriModel::TIPI_LABEL,
            'statiLabel' => CantieriModel::STATI_LABEL,
            'from'       => $this->request->getGet('from'),
        ]);
    }

    /**
     * Salva il nuovo cantiere.
     */
    public function store(): RedirectResponse
    {
        $model = new CantieriModel();

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $cantiereId = $model->insert($this->request->getPost(), true);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : base_url('cantieri/' . $cantiereId);

        return redirect()->to($dest)->with('success', 'Cantiere creato.');
    }

    /**
     * Scheda cantiere: header (cantiere + cliente) + diario cronologico + interventi collegati.
     */
    public function show(int $id): string|RedirectResponse
    {
        $cantiere = (new CantieriModel())->conCliente($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        return view('cantieri/show', [
            'title'                => 'Cantiere',
            'cantiere'             => $cantiere,
            'note'                 => (new CantieriNoteModel())->perCantiere($id),
            'interventi'           => (new InterventiModel())->perCantiere($id),
            'tipiLabel'            => CantieriModel::TIPI_LABEL,
            'statiLabel'           => CantieriModel::STATI_LABEL,
            'statiBadge'           => CantieriModel::STATI_BADGE,
            'interventiStatiLabel' => InterventiModel::STATI_LABEL,
            'interventiBadge'      => InterventiModel::STATI_BADGE,
        ]);
    }

    /**
     * Stampa PDF riepilogativa del cantiere: anagrafica cliente e dati cantiere completi,
     * diario integrale (nessun troncamento) e tutti gli interventi collegati con i relativi
     * materiali (portati e da portare) — a differenza del PDF Cliente non filtra per stato,
     * è pensato come riepilogo storico completo, non come documento "cosa resta da fare".
     */
    public function pdf(int $id): string|RedirectResponse
    {
        $cantiere = (new CantieriModel())->conCliente($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        $cliente = (new ClientiModel())->find($cantiere['cliente_id']);

        $interventi     = (new InterventiModel())->perCantiere($id);
        $materialiModel = new InterventiMaterialiModel();
        foreach ($interventi as &$iv) {
            $iv['materiali'] = $materialiModel->perIntervento($iv['id']);
        }
        unset($iv);

        $html = view('cantieri/pdf_scheda_cantiere', [
            'cantiere'             => $cantiere,
            'cliente'              => $cliente,
            'note'                 => (new CantieriNoteModel())->perCantiere($id),
            'interventi'           => $interventi,
            'tipiLabel'            => CantieriModel::TIPI_LABEL,
            'statiLabel'           => CantieriModel::STATI_LABEL,
            'interventiStatiLabel' => InterventiModel::STATI_LABEL,
            'materialiStatiLabel'  => InterventiMaterialiModel::STATI_LABEL,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('cantiere-' . $cantiere['id'] . '.pdf', ['Attachment' => false]);
        exit;
    }

    /**
     * Form modifica cantiere.
     */
    public function edit(int $id): string|RedirectResponse
    {
        $cantiere = (new CantieriModel())->find($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        return view('cantieri/edit', [
            'title'      => 'Modifica cantiere',
            'cantiere'   => $cantiere,
            'cliente'    => (new ClientiModel())->find($cantiere['cliente_id']),
            'clienti'    => (new ClientiModel())->orderBy('ragsoc')->findAll(),
            'tipi'       => (new TipiInterventoModel())->attivi(),
            'tipiLabel'  => CantieriModel::TIPI_LABEL,
            'statiLabel' => CantieriModel::STATI_LABEL,
            'from'       => $this->request->getGet('from'),
        ]);
    }

    /**
     * Aggiorna i dati del cantiere.
     */
    public function update(int $id): RedirectResponse
    {
        $model    = new CantieriModel();
        $cantiere = $model->find($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : base_url('cantieri/' . $id);

        return redirect()->to($dest)->with('success', 'Cantiere aggiornato.');
    }

    /**
     * Cambia lo stato del cantiere (aperto / sospeso / chiuso).
     * Nessun effetto a cascata sugli interventi: chiudere un cantiere non tocca
     * gli interventi già pianificati o completati, è solo un'etichetta di stato.
     */
    public function cambiaStato(int $id): RedirectResponse
    {
        $model    = new CantieriModel();
        $cantiere = $model->find($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        $nuovoStato = $this->request->getPost('stato');
        if (! array_key_exists($nuovoStato, CantieriModel::STATI_LABEL)) {
            return redirect()->to('cantieri/' . $id)->with('error', 'Stato non valido.');
        }

        $model->update($id, ['stato' => $nuovoStato]);

        return redirect()->to('cantieri/' . $id)
            ->with('success', 'Stato aggiornato a "' . CantieriModel::STATI_LABEL[$nuovoStato] . '".');
    }

    /**
     * Elimina un cantiere (hard delete con controllo preventivo).
     * Bloccato se ha interventi collegati: vanno prima scollegati o eliminati.
     * Le note del diario cadono automaticamente (FK CASCADE).
     */
    public function delete(int $id): RedirectResponse
    {
        $model    = new CantieriModel();
        $cantiere = $model->find($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        $numInterventi = (new InterventiModel())->where('cantiere_id', $id)->countAllResults();
        if ($numInterventi > 0) {
            return redirect()->to('cantieri/' . $id)
                ->with('error', "Impossibile eliminare: il cantiere ha {$numInterventi} interventi collegati. Scollegali o eliminali prima.");
        }

        $clienteId = (int) $cantiere['cliente_id'];
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : base_url('clienti/' . $clienteId);

        return redirect()->to($dest)->with('success', 'Cantiere eliminato.');
    }

    /**
     * Salva la posizione del pin impostata manualmente dalla scheda cantiere
     * (mappa Leaflet, stesso pattern di ClientiController::aggiornaPosizione()).
     * Sostituisce un'eventuale geocodifica automatica fallita.
     */
    public function aggiornaPosizione(int $id): RedirectResponse
    {
        $model    = new CantieriModel();
        $cantiere = $model->find($id);
        if (! $cantiere) {
            return redirect()->to('cantieri')->with('error', 'Cantiere non trovato.');
        }

        $rules = [
            'lat' => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'lng' => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('cantieri/' . $id . '#sec-posizione')->with('error', 'Coordinate non valide.');
        }

        $model->update($id, [
            'lat'                 => $this->request->getPost('lat'),
            'lng'                 => $this->request->getPost('lng'),
            'geocoded_at'         => date('Y-m-d H:i:s'),
            'geocodifica_fallita' => 0,
        ]);

        return redirect()->to('cantieri/' . $id . '#sec-posizione')->with('success', 'Posizione aggiornata.');
    }

    /**
     * Aggiunge una nota al diario del cantiere, tornando alla pagina di origine.
     */
    public function aggiungiNota(): RedirectResponse
    {
        if (! $this->validate([
            'cantiere_id' => 'required|is_natural_no_zero',
            'data_nota'   => 'permit_empty|valid_date[Y-m-d]',
            'testo'       => 'required|min_length[2]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $cantiereId = (int) $this->request->getPost('cantiere_id');
        (new CantieriNoteModel())->insert($this->request->getPost());

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'cantieri/' . $cantiereId . '#sec-diario';

        return redirect()->to($dest)->with('success', 'Nota aggiunta al diario.');
    }
   
    /**
     * Elimina una nota dal diario, tornando alla scheda del cantiere di origine.
     */
    public function eliminaNota(int $id): RedirectResponse
    {
        $model = new CantieriNoteModel();
        $nota  = $model->find($id);
        if (! $nota) {
            return redirect()->back()->with('error', 'Nota non trovata.');
        }

        $cantiereId = (int) $nota['cantiere_id'];
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'cantieri/' . $cantiereId . '#sec-diario';

        return redirect()->to($dest)->with('success', 'Nota eliminata.');
    }

    // -------------------------------------------------------------------------

    /**
     * Regole di validazione comuni per insert e update.
     * I valori ammessi per tipo e stato vengono letti dalle costanti del model.
     */
    private function regolaValidazione(): array
    {
        $tipiAmmessi  = implode(',', array_keys(CantieriModel::TIPI_LABEL));
        $statiAmmessi = implode(',', array_keys(CantieriModel::STATI_LABEL));

        return [
            'cliente_id'         => 'required|is_natural_no_zero',
            'titolo'             => 'required|min_length[2]|max_length[150]',
            'tipo'               => "required|in_list[{$tipiAmmessi}]",
            'stato'              => "required|in_list[{$statiAmmessi}]",
            'indirizzo'          => 'permit_empty|max_length[255]',
            'citta'              => 'permit_empty|max_length[100]',
            'referente_nome'     => 'permit_empty|max_length[150]',
            'referente_telefono' => 'permit_empty|max_length[50]',
            'data_inizio'        => 'permit_empty|valid_date[Y-m-d]',
            'data_fine_prevista' => [
                'rules'  => 'permit_empty|valid_date[Y-m-d]|non_anteriore_a[data_inizio]',
                'errors' => [
                    'non_anteriore_a' => 'La data di fine prevista non può essere anteriore alla data di inizio.',
                ],
            ],
        ];
    }
}
