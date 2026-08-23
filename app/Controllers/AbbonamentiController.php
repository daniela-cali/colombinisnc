<?php

namespace App\Controllers;

use App\Models\AbbonamentiModel;
use App\Models\AbbonamentiPeriodiModel;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\TipiInterventoModel;
use CodeIgniter\HTTP\RedirectResponse;

class AbbonamentiController extends BaseController
{
    /**
     * Index globale: tutti gli abbonamenti con cliente, tipo e stato calcolato.
     */
    public function index(): string
    {
        $abbonamenti  = (new AbbonamentiModel())->elencoConDettagli();
        $tipiPresenti = array_values(array_unique(array_filter(array_column($abbonamenti, 'tipo_nome'))));
        sort($tipiPresenti);
        $anniPresenti = array_values(array_unique(array_filter(array_column($abbonamenti, 'anno_inizio'))));
        rsort($anniPresenti);

        return view('abbonamenti/index', [
            'title'        => 'Abbonamenti',
            'abbonamenti'  => $abbonamenti,
            'tipiPresenti' => $tipiPresenti,
            'anniPresenti' => $anniPresenti,
            'statiLabel'   => AbbonamentiModel::STATI_LABEL,
            'statiBadge'   => AbbonamentiModel::STATI_BADGE,
            'frequenze'    => AbbonamentiModel::FREQUENZE_LABEL,
            'help_sezione' => 'abbonamenti',
        ]);
    }

    /**
     * Form nuovo abbonamento.
     * Accetta ?cliente_id=N per pre-compilare il cliente (link da scheda cliente).
     * Accetta ?from=URL per tornare alla pagina di origine dopo il salvataggio.
     */
    public function nuovo(): string
    {
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);

        return view('abbonamenti/nuovo', [
            'title'     => 'Nuova proposta di abbonamento',
            'cliente'   => $clienteId ? (new ClientiModel())->find($clienteId) : null,
            'clienti'   => (new ClientiModel())->orderBy('ragsoc')->findAll(),
            'tipi'      => (new TipiInterventoModel())->abbonabili(),
            'frequenze' => AbbonamentiModel::FREQUENZE_LABEL,
            'periodi'   => null,
            'from'      => $this->request->getGet('from'),
        ]);
    }

    /**
     * Salva il nuovo abbonamento, la generazioni degli interventi è esclusiva dello stato attivo, prima è solo una proposta.
     */
    public function store(): RedirectResponse
    {
        $model = new AbbonamentiModel();

        // Prima della validazione: le regole su periodi.*.* danno per scontato che almeno
        // una riga esista, e senza questo controllo il messaggio dedicato andrebbe perso.
        $periodi = $this->request->getPost('periodi') ?? [];
        if (empty($periodi)) {
            return redirect()->back()->withInput()
                ->with('errors', ['periodi' => 'Aggiungere almeno un periodo di frequenza.']);
        }

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (! $this->periodiCoprono($periodi, $this->request->getPost('data_inizio'), $this->request->getPost('data_fine'))) {
            return redirect()->back()->withInput()
                ->with('errors', ['periodi' => 'I periodi devono coprire l\'intero arco dell\'abbonamento: il primo deve iniziare e l\'ultimo deve finire alle date del periodo di validità.']);
        }

        $db = db_connect();
        $db->transStart();
        //Proposta non arriva nel post ed è default db, ma lo passo lo stesso forzandolo in inserimento nuovo abbonamento
        $abbonamentoId = $model->insert(array_merge($this->request->getPost(), ['stato' => AbbonamentiModel::STATO_PROPOSTA]), true);
        $this->salvaPeriodi($abbonamentoId, $periodi);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()
                ->with('error', 'Errore durante la creazione dell\'abbonamento.');
        }

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : base_url('abbonamenti/' . $abbonamentoId);

        return redirect()->to($dest)->with('success', "Abbonamento creato.");
    }

    /**
     * Scheda abbonamento: dati chiave + periodi + lista interventi figli ordinati per data_scadenza.
     */
    public function show(int $id): string|RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->trovaConDettagli($id);

        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        $interventi = (new InterventiModel())->perAbbonamento($id);

        return view('abbonamenti/show', [
            'title'                => 'Abbonamento',
            'abbonamento'          => $abbonamento,
            'periodi'              => (new AbbonamentiPeriodiModel())->perAbbonamento($id),
            'interventi'           => $interventi,
            'statiLabel'           => AbbonamentiModel::STATI_LABEL,
            'statiBadge'           => AbbonamentiModel::STATI_BADGE,
            'frequenze'            => AbbonamentiModel::FREQUENZE_LABEL,
            'interventiStatiLabel' => InterventiModel::STATI_LABEL,
            'interventiBadge'      => InterventiModel::STATI_BADGE,
        ]);
    }

    /**
     * Form modifica metadati (periodi, prezzo, note, date).
     * Non rigenera gli interventi — quelli esistono già.
     */
    public function edit(int $id): string|RedirectResponse
    {
        $abbonamento = (new AbbonamentiModel())->find($id);
        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        return view('abbonamenti/edit', [
            'title'       => 'Modifica abbonamento',
            'abbonamento' => $abbonamento,
            'cliente'     => (new ClientiModel())->find($abbonamento['cliente_id']),
            'tipi'        => (new TipiInterventoModel())->abbonabili(),
            'frequenze'   => AbbonamentiModel::FREQUENZE_LABEL,
            'periodi'     => (new AbbonamentiPeriodiModel())->perAbbonamento($id),
            'from'        => $this->request->getGet('from'),
        ]);
    }

    /**
     * Aggiorna i metadati dell'abbonamento e i periodi.
     * Non rigenera gli interventi già creati.
     */
    public function update(int $id): RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->find($id);
        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        // Prima della validazione: le regole su periodi.*.* danno per scontato che almeno
        // una riga esista, e senza questo controllo il messaggio dedicato andrebbe perso.
        $periodi = $this->request->getPost('periodi') ?? [];
        if (empty($periodi)) {
            return redirect()->back()->withInput()
                ->with('errors', ['periodi' => 'Aggiungere almeno un periodo di frequenza.']);
        }

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (! $this->periodiCoprono($periodi, $this->request->getPost('data_inizio'), $this->request->getPost('data_fine'))) {
            return redirect()->back()->withInput()
                ->with('errors', ['periodi' => 'I periodi devono coprire l\'intero arco dell\'abbonamento: il primo deve iniziare e l\'ultimo deve finire alle date del periodo di validità.']);
        }

        // I periodi si sostituiscono in blocco: la cancellazione e il reinserimento devono
        // stare nella stessa transazione, altrimenti un errore a metà lascerebbe
        // l'abbonamento con periodi parziali o senza — cioè senza frequenza delle visite.
        $db = db_connect();
        $db->transStart();

        $model->update($id, $this->request->getPost());
        (new AbbonamentiPeriodiModel())->where('abbonamento_id', $id)->delete();
        $this->salvaPeriodi($id, $periodi);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()
                ->with('error', 'Errore durante l\'aggiornamento dell\'abbonamento: nessuna modifica è stata salvata.');
        }

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : base_url('abbonamenti/' . $id);

        return redirect()->to($dest)->with('success', 'Abbonamento aggiornato.');
    }

    /**
     * Cambia lo stato dell'abbonamento con i relativi effetti sugli interventi figli.
     *
     * Transizioni consentite:
     * - proposta   → attivo   : accettazione unico punto in cui si generano gli interventi
     *              → rifiutata: fase terminale, nessun effetto
     * - rifiutata  → proposta : ripensamento o modifica elementi della proposta
     * - attivo     → sospeso  : interventi futuri da_pianificare → sospeso
     * - sospeso    → attivo   : POST ripristina=1 → da_pianificare; 0 → annullato
     * - attivo/sospeso → disdetto: tutti i futuri da_pianificare e sospeso → annullato
     */
    public function cambiaStato(int $id): RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->find($id);
        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        $nuovoStato   = $this->request->getPost('stato');
        $statoAttuale = $abbonamento['stato'];

        $transizioni = [
            'proposta'   => [AbbonamentiModel::STATO_ATTIVO, AbbonamentiModel::STATO_RIFIUTATA],
            'rifiutata'  => [AbbonamentiModel::STATO_PROPOSTA],
            'attivo'     => [AbbonamentiModel::STATO_SOSPESO, AbbonamentiModel::STATO_DISDETTO],
            'sospeso'    => [AbbonamentiModel::STATO_ATTIVO,  AbbonamentiModel::STATO_DISDETTO],
        ];

        if (! isset($transizioni[$statoAttuale]) || ! in_array($nuovoStato, $transizioni[$statoAttuale], true)) {
            return redirect()->to('abbonamenti/' . $id)->with('error', 'Transizione di stato non consentita.');
        }

        $db = db_connect();
        $db->transStart();

        $model->update($id, ['stato' => $nuovoStato]);

        if ($nuovoStato === AbbonamentiModel::STATO_SOSPESO) {
            $model->sospendiInterventi($id);
            $msg = 'Abbonamento sospeso. Gli interventi futuri sono stati sospesi.';

        } elseif ($nuovoStato === AbbonamentiModel::STATO_ATTIVO) {
            if ($this->request->getPost('ripristina') === '1') {
                $model->ripristinaInterventi($id);
                $msg = 'Abbonamento riattivato. Gli interventi sospesi sono tornati nel pool.';
            } else {
                $model->annullaInterventi($id);
                $msg = 'Abbonamento riattivato. Gli interventi sospesi sono stati annullati.';
            }
        } elseif ($nuovoStato === AbbonamentiModel::STATO_DISDETTO) {
            $model->annullaInterventi($id);
            $msg = 'Abbonamento disdetto. Gli interventi futuri sono stati annullati.';
        } else {
            $msg = 'Stato aggiornato.';
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('abbonamenti/' . $id)->with('error', 'Errore durante il cambio di stato. Riprovare.');
        }

        return redirect()->to('abbonamenti/' . $id)->with('success', $msg);
    }

    /**
     * Apre il form nuovo pre-compilato con i dati dell'abbonamento precedente.
     * Date spostate di un anno; periodi spostati di un anno; abbonamento_precedente_id impostato.
     */
    public function rinnova(int $id): string|RedirectResponse
    {
        $precedente = (new AbbonamentiModel())->find($id);
        if (! $precedente) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        $precompilato = array_merge($precedente, [
            'id'                        => null,
            'data_inizio'               => date('Y-m-d', strtotime($precedente['data_inizio'] . ' +1 year')),
            'data_fine'                 => date('Y-m-d', strtotime($precedente['data_fine']   . ' +1 year')),
            'abbonamento_precedente_id' => $id,
            'stato'                     => AbbonamentiModel::STATO_PROPOSTA,
        ]);

        $periodi = (new AbbonamentiPeriodiModel())->perAbbonamento($id);
        $periodiPrecompilati = array_map(fn ($p) => array_merge($p, [
            'id'             => null,
            'abbonamento_id' => null,
            'data_inizio'    => date('Y-m-d', strtotime($p['data_inizio'] . ' +1 year')),
            'data_fine'      => date('Y-m-d', strtotime($p['data_fine']   . ' +1 year')),
        ]), $periodi);

        return view('abbonamenti/nuovo', [
            'title'       => 'Rinnova abbonamento',
            'abbonamento' => $precompilato,
            'cliente'     => (new ClientiModel())->find($precedente['cliente_id']),
            'clienti'     => [],
            'tipi'        => (new TipiInterventoModel())->abbonabili(),
            'frequenze'   => AbbonamentiModel::FREQUENZE_LABEL,
            'periodi'     => $periodiPrecompilati,
            'from'        => $this->request->getGet('from'),
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Inserisce i periodi per un abbonamento, assegnando ordine progressivo.
     *
     * Presuppone righe complete: le regole periodi.*.* di regolaValidazione() girano in
     * entrambi i chiamanti prima del salvataggio. Nessuno scarto silenzioso quindi — e se
     * una riga incompleta ci arrivasse comunque, le colonne NOT NULL della tabella fanno
     * fallire l'INSERT, la transazione va in rollback e l'utente vede l'errore.
     */
    private function salvaPeriodi(int $abbonamentoId, array $periodi): void
    {
        $model  = new AbbonamentiPeriodiModel();
        $ordine = 1;
        foreach ($periodi as $periodo) {
            $model->insert([
                'abbonamento_id'    => $abbonamentoId,
                'data_inizio'       => $periodo['data_inizio'],
                'data_fine'         => $periodo['data_fine'],
                'frequenza'         => $periodo['frequenza'],
                'con_pulizia_fondo' => (int) ($periodo['con_pulizia_fondo'] ?? 0),
                'ordine'            => $ordine++,
            ]);
        }
    }

    /**
     * Verifica che i periodi coprano l'intero arco dell'abbonamento: il primo periodo deve
     * iniziare quando inizia l'abbonamento, l'ultimo deve finire quando l'abbonamento finisce.
     * Evita date scoperte per cui nessun periodo definirebbe la frequenza delle visite.
     */
    private function periodiCoprono(array $periodi, string $dataInizioAbbonamento, string $dataFineAbbonamento): bool
    {
        $inizi = array_filter(array_column($periodi, 'data_inizio'));
        $fine  = array_filter(array_column($periodi, 'data_fine'));

        if (empty($inizi) || empty($fine)) {
            return false;
        }

        return min($inizi) === $dataInizioAbbonamento && max($fine) === $dataFineAbbonamento;
    }

    /**
     * Accetta un singolo abbonamento in transazione propria: aggiorna lo stato e genera gli interventi.
     * Nessuna dipendenza da HTTP/redirect — richiamato sia da accetta() (singola) sia,
     * in futuro, da accettaMultiplo() (in loop, una transazione per id).
     */
    private function accettaAbbonamento(int $id): array
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->find($id);

        if (! $abbonamento || $abbonamento['stato'] !== AbbonamentiModel::STATO_PROPOSTA) {
            return ['ok' => false, 'n' => 0];
        }

        $db = db_connect();
        $db->transStart();

        $model->update($id, ['stato' => AbbonamentiModel::STATO_ATTIVO]);
        $n = $model->generaInterventi($id, $abbonamento);

        $db->transComplete();

        return ['ok' => $db->transStatus(), 'n' => $n];
    }

    /**
     * Accetta una proposta: passa lo stato ad attivo e genera gli interventi.
     */
    public function accetta(int $id): RedirectResponse
    {
        $esito = $this->accettaAbbonamento($id);

        if (! $esito['ok']) {
            return redirect()->to('abbonamenti/' . $id)->with('error', 'Impossibile accettare la proposta.');
        }

        return redirect()->to('abbonamenti')->with('success', "Proposta accettata, {$esito['n']} interventi generati.");
    }

    /**
     * Accetta in blocco più proposte selezionate nell'index (checkbox).
     * Una transazione per abbonamento (vedi accettaAbbonamento()): un fallimento
     * su un id non blocca gli altri.
     */
    public function accettaMultiplo(): RedirectResponse
    {
        $ids = array_map('intval', $this->request->getPost('ids') ?? []);

        if (empty($ids)) {
            return redirect()->to('abbonamenti')->with('error', 'Nessuna proposta selezionata.');
        }

        $accettati = 0;
        $falliti   = 0;

        foreach ($ids as $id) {
            if ($this->accettaAbbonamento($id)['ok']) {
                $accettati++;
            } else {
                $falliti++;
            }
        }

        $msg = "{$accettati} proposte accettate.";
        if ($falliti > 0) {
            $msg .= " {$falliti} non riuscite.";
        }

        return redirect()->to('abbonamenti')->with($falliti > 0 ? 'warning' : 'success', $msg);
    }

    public function rifiuta(int $id): RedirectResponse
    {
        $abbonamento = (new AbbonamentiModel())->find($id);
        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }
        if ($abbonamento['stato'] !== AbbonamentiModel::STATO_PROPOSTA){
            return redirect()->to('abbonamenti')->with('error', 'Questo abbonamento non è in stato proposta.');
        }
        $data['stato'] = AbbonamentiModel::STATO_RIFIUTATA;
        (new AbbonamentiModel())->update($id, $data);
        return redirect()->to('abbonamenti')->with('success', 'Proposta rifiutata correttamente.');
    }

    private function regolaValidazione(): array
    {
        return [
            'cliente_id'         => 'required|is_natural_no_zero',
            'tipo_intervento_id' => 'required|is_natural_no_zero',
            'data_inizio'        => 'required|valid_date[Y-m-d]',
            'data_fine'          => 'required|valid_date[Y-m-d]',
            'prezzo'             => 'permit_empty|decimal',

            // Le regole con il jolly valgono per ogni riga di periodi[]. Il "required" sul
            // <select> della frequenza vive solo nel browser: senza queste regole una riga
            // incompleta arriverebbe fino al salvataggio e verrebbe scartata in silenzio,
            // lasciando l'abbonamento senza la frequenza che ne definisce le visite.
            'periodi.*.data_inizio' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Ogni periodo deve avere una data di inizio.',
                    'valid_date' => 'La data di inizio di un periodo non è valida.',
                ],
            ],
            'periodi.*.data_fine' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Ogni periodo deve avere una data di fine.',
                    'valid_date' => 'La data di fine di un periodo non è valida.',
                ],
            ],
            'periodi.*.frequenza' => [
                'rules'  => 'required|in_list[' . implode(',', array_keys(AbbonamentiModel::FREQUENZE_LABEL)) . ']',
                'errors' => [
                    'required' => 'Ogni periodo deve avere una frequenza.',
                    'in_list'  => 'La frequenza di un periodo non è tra quelle previste.',
                ],
            ],
        ];
    }
}
