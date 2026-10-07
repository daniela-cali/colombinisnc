<?php

namespace App\Controllers;

use App\Libraries\PropostaAbbonamento;
use App\Models\AbbonamentiModel;
use App\Models\AbbonamentiPeriodiModel;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\TipiInterventoModel;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use ZipArchive;

class AbbonamentiController extends BaseController
{
    /**
     * Stati degli interventi che impediscono di annullare l'accettazione di un abbonamento.
     *
     * Non è una lista di "interventi importanti": è il confine oltre il quale l'informazione
     * è uscita dall'azienda o il lavoro è stato fatto. Un intervento pianificato ha una data
     * che il cliente conosce già; uno completato è storico, con i suoi materiali. Restano
     * ammessi da_pianificare, sospeso e annullato, che nessuno ha ancora toccato.
     */
    private const STATI_BLOCCANO_ANNULLAMENTO = [
        InterventiModel::STATO_PIANIFICATO,
        InterventiModel::STATO_IN_CORSO,
        InterventiModel::STATO_COMPLETATO,
    ];

    /**
     * Index globale: tutti gli abbonamenti con cliente, tipo e stato calcolato.
     */
    public function index(): string
    {
        $model = new AbbonamentiModel();

        // Il flag lo calcola il model, non la view: la stessa regola decide se mostrare il
        // bottone Rinnova e se rinnova() accetta la richiesta, quindi non possono divergere
        // come facevano index e scheda, che offrivano il rinnovo su stati diversi.
        $abbonamenti = array_map(
            static fn (array $a): array => $a + ['rinnovabile' => $model->rinnovabile($a)],
            $model->elencoConDettagli()
        );

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
            'coda'      => null,
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

        // Rinnovo in coda: si passa al prossimo form invece di tornare alla pagina di origine.
        $coda = $this->leggiCoda($this->request->getPost());
        if ($coda !== null) {
            $coda['fatti']++;

            return redirect()->to($this->urlProssimo($coda))->with('success', 'Proposta di rinnovo creata.');
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
            'rinnovabile'          => $model->rinnovabile($abbonamento),
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
     * - attivo/sospeso → disdetto: tutti i futuri → annullato, comprese le visite già
     *                              pianificate, di cui il messaggio avvisa perché il cliente
     *                              ne conosce già la data
     * - disdetto   → attivo   : solo lo stato. Le visite annullate dalla disdetta restano
     *                           annullate; serve a poter poi rinnovare un cliente che torna
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
            'disdetto'   => [AbbonamentiModel::STATO_ATTIVO],
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

        } elseif ($nuovoStato === AbbonamentiModel::STATO_ATTIVO && $statoAttuale === AbbonamentiModel::STATO_DISDETTO) {
            // Nessuna operazione sugli interventi: ripristinare quelli annullati dalla disdetta
            // rimetterebbe in calendario visite che nessuno ha più concordato con il cliente.
            $msg = 'Abbonamento riattivato. Le visite annullate con la disdetta non sono state ripristinate.';

        } elseif ($nuovoStato === AbbonamentiModel::STATO_ATTIVO) {
            if ($this->request->getPost('ripristina') === '1') {
                $model->ripristinaInterventi($id);
                $msg = 'Abbonamento riattivato. Gli interventi sospesi sono tornati nel pool.';
            } else {
                $model->annullaInterventi($id);
                $msg = 'Abbonamento riattivato. Gli interventi sospesi sono stati annullati.';
            }
        } elseif ($nuovoStato === AbbonamentiModel::STATO_DISDETTO) {
            $esito = $model->annullaInterventi($id, true);

            if ($esito['totale'] === 0) {
                $msg = 'Abbonamento disdetto. Non c\'erano interventi futuri da annullare.';
            } else {
                $msg = 'Abbonamento disdetto. ' . $esito['totale']
                    . ($esito['totale'] === 1 ? ' intervento futuro annullato' : ' interventi futuri annullati') . '.';

                // La data era già stata comunicata al cliente: l'annullamento in calendario
                // non basta, qualcuno deve telefonare.
                if ($esito['pianificati'] > 0) {
                    $msg .= ' Attenzione: ' . $esito['pianificati']
                        . ($esito['pianificati'] === 1 ? ' era già pianificato' : ' erano già pianificati')
                        . ' — avvisa il cliente.';
                }
            }
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
     * Il prezzo è quello dell'anno prima aumentato secondo AbbonamentiModel::prezzoRinnovo(),
     * correggibile nel form.
     */
    public function rinnova(int $id): string|RedirectResponse
    {
        $model      = new AbbonamentiModel();
        $precedente = $model->trovaConDettagli($id);
        if (! $precedente) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        // Il bottone non compare quando non si può rinnovare, ma la rotta resta raggiungibile
        // per URL o da una pagina rimasta aperta: qui si applica la stessa regola, e il motivo
        // arriva dal model per non rischiare di spiegare all'utente un rifiuto diverso da
        // quello effettivo. Serve trovaConDettagli() e non find(), perché rinnovabile() legge
        // stato_calcolato e successore_id.
        $coda = $this->leggiCoda($this->request->getGet());

        $motivo = $model->motivoNonRinnovabile($precedente);
        if ($motivo !== null) {
            // In coda non si ferma tutto: l'abbonamento va fra i saltati e il riepilogo
            // finale ne dirà il motivo (spec rinnovo multiplo, punto 8).
            if ($coda !== null) {
                $coda['saltati'][] = $id;

                return redirect()->to($this->urlProssimo($coda));
            }

            return redirect()->to('abbonamenti/' . $id)
                ->with('error', 'Questo abbonamento ' . $motivo . '.');
        }

        $precompilato = array_merge($precedente, [
            'id'                        => null,
            'data_inizio'               => date('Y-m-d', strtotime($precedente['data_inizio'] . ' +1 year')),
            'data_fine'                 => date('Y-m-d', strtotime($precedente['data_fine']   . ' +1 year')),
            'abbonamento_precedente_id' => $id,
            'stato'                     => AbbonamentiModel::STATO_PROPOSTA,
            'prezzo'                    => $model->prezzoRinnovo($precedente['prezzo']),
            // Solo per la riga di spiegazione sotto il prezzo: non sono campi del form.
            'prezzo_precedente'         => $precedente['prezzo'],
            'aumento_percento'          => AbbonamentiModel::percentualeRinnovo(),
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
            'coda'        => $coda === null ? null : $coda + [
                'posizione'     => $coda['fatti'] + count($coda['saltati']) + 1,
                'totale'        => $coda['fatti'] + count($coda['saltati']) + 1 + count($coda['coda']),
                'urlSalta'      => $this->urlProssimo(['saltati' => [...$coda['saltati'], $id]] + $coda),
                'urlInterrompi' => $this->urlFine($coda, count($coda['coda']) + 1),
            ],
        ]);
    }

    /**
     * Riepilogo del rinnovo in coda, alla fine o dopo Interrompi: quante proposte sono nate,
     * chi è stato saltato e perché, quanti ne restavano. Poi torna all'elenco, che ricorda i
     * filtri, così si riprende da dove si era.
     *
     * Il motivo di un saltato si chiede a motivoNonRinnovabile() adesso: se l'abbonamento è
     * ancora rinnovabile l'ha saltato l'operatore, altrimenti la coda l'ha scartato da sola.
     */
    public function fineRinnovo(): RedirectResponse
    {
        $coda     = $this->leggiCoda($this->request->getGet()) ?? ['fatti' => 0, 'saltati' => []];
        $restanti = (int) $this->request->getGet('restanti');

        $risposta = redirect()->to('abbonamenti')->with('success', match ($coda['fatti']) {
            0       => 'Nessuna proposta di rinnovo creata.',
            1       => 'Creata 1 proposta di rinnovo.',
            default => 'Create ' . $coda['fatti'] . ' proposte di rinnovo.',
        });

        $avvisi = [];

        if ($coda['saltati']) {
            $model = new AbbonamentiModel();
            $nomi  = [];
            foreach ($coda['saltati'] as $id) {
                $abbonamento = $model->trovaConDettagli($id);
                if (! $abbonamento) {
                    continue;
                }
                $motivo = $model->motivoNonRinnovabile($abbonamento);
                $nomi[] = esc($abbonamento['cliente_denominazione']) . ($motivo !== null ? ' (' . esc($motivo) . ')' : '');
            }
            $avvisi[] = (count($coda['saltati']) === 1 ? 'Saltato 1: ' : 'Saltati ' . count($coda['saltati']) . ': ')
                . implode(', ', $nomi) . '.';
        }

        if ($restanti > 0) {
            $avvisi[] = 'Rinnovo interrotto: ' . ($restanti === 1 ? 'ne restava 1' : 'ne restavano ' . $restanti) . ' da fare.';
        }

        return $avvisi ? $risposta->with('warning', implode(' ', $avvisi)) : $risposta;
    }

    /**
     * Stato del rinnovo in coda letto da query string o POST, o null se non si è in coda.
     *
     * La presenza del parametro coda, anche vuoto, è ciò che distingue il rinnovo in coda dal
     * singolo: l'ultimo form di una coda ha coda vuota. I valori arrivano dall'indirizzo,
     * quindi si tengono solo interi positivi e senza doppioni.
     *
     * @return array{coda: list<int>, fatti: int, saltati: list<int>}|null
     */
    private function leggiCoda(array $sorgente): ?array
    {
        if (! array_key_exists('coda', $sorgente)) {
            return null;
        }

        $ids = static fn ($valore): array => array_values(array_unique(array_filter(
            array_map('intval', explode(',', (string) $valore)),
            static fn (int $id): bool => $id > 0
        )));

        return [
            'coda'    => $ids($sorgente['coda']),
            'fatti'   => max(0, (int) ($sorgente['fatti'] ?? 0)),
            'saltati' => $ids($sorgente['saltati'] ?? ''),
        ];
    }

    /**
     * Dove andare dopo il form corrente: il rinnovo del prossimo id, con il resto della coda
     * nell'indirizzo, oppure il riepilogo se la coda è finita.
     */
    private function urlProssimo(array $coda): string
    {
        if (! $coda['coda']) {
            return $this->urlFine($coda, 0);
        }

        $prossimo = array_shift($coda['coda']);

        return base_url('abbonamenti/' . $prossimo . '/rinnova') . '?' . http_build_query([
            'coda'    => implode(',', $coda['coda']),
            'fatti'   => $coda['fatti'],
            'saltati' => implode(',', $coda['saltati']),
        ]);
    }

    /**
     * Indirizzo del riepilogo; restanti è quanti form non sono stati aperti, zero se la coda
     * è arrivata in fondo.
     */
    private function urlFine(array $coda, int $restanti): string
    {
        return base_url('abbonamenti/rinnovo-fine') . '?' . http_build_query([
            'coda'     => '',
            'fatti'    => $coda['fatti'],
            'saltati'  => implode(',', $coda['saltati']),
            'restanti' => $restanti,
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

    /**
     * Proposta in Word dell'abbonamento, da scaricare. Si genera in qualunque stato, anche per
     * ristampare quella di un abbonamento già attivo; il file non resta sul server.
     *
     * È un GET pur scrivendo proposta_generata_at: è un'annotazione innocua, e il bottone della
     * scheda resta un semplice link.
     */
    public function proposta(int $id): DownloadResponse|RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->trovaConDettagli($id);

        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        try {
            $proposta = (new PropostaAbbonamento())->genera($abbonamento);
        } catch (RuntimeException $e) {
            return redirect()->to('abbonamenti/' . $id)->with('error', $e->getMessage());
        }

        $model->segnaPropostaGenerata([$id]);

        return $this->response->download($proposta['nome'], $proposta['contenuto'], true);
    }

    /**
     * Proposte in Word delle righe selezionate nell'index, in un unico zip: un file per
     * proposta, da allegare all'email o da stampare tutti insieme.
     *
     * Una proposta che non si può generare non blocca le altre: finisce in NON GENERATE.txt con
     * il motivo, perché una risposta che scarica un file non può portare un messaggio flash.
     * Se non se ne genera nessuna non c'è zip, si torna all'elenco con l'errore.
     */
    public function proposteWord(): DownloadResponse|RedirectResponse
    {
        $ids = array_map('intval', $this->request->getPost('ids') ?? []);

        if (empty($ids)) {
            return redirect()->to('abbonamenti')->with('error', 'Nessuna proposta selezionata.');
        }

        $model      = new AbbonamentiModel();
        $generatore = new PropostaAbbonamento();
        $file       = tempnam(sys_get_temp_dir(), 'proposte');
        $zip        = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        $nomiUsati = [];
        $generate  = [];
        $scartate  = [];

        foreach ($ids as $id) {
            $abbonamento = $model->trovaConDettagli($id);
            if (! $abbonamento) {
                continue;
            }

            try {
                $proposta = $generatore->genera($abbonamento);
            } catch (RuntimeException $e) {
                $scartate[] = $abbonamento['cliente_denominazione'] . " (abbonamento {$id}): " . $e->getMessage();
                continue;
            }

            $zip->addFromString($this->nomeLibero($proposta['nome'], $nomiUsati), $proposta['contenuto']);
            $generate[] = $id;
        }

        if ($scartate !== [] && $generate !== []) {
            $zip->addFromString('NON GENERATE.txt', implode("\r\n", $scartate) . "\r\n");
        }
        $zip->close();

        // Uno zip chiuso senza file non viene scritto: il file di tempnam() può esserci o no.
        $contenuto = is_file($file) ? file_get_contents($file) : '';
        if (is_file($file)) {
            unlink($file);
        }

        if ($generate === []) {
            return redirect()->to('abbonamenti')->with('error', 'Nessuna proposta generata. ' . implode(' ', $scartate));
        }

        $model->segnaPropostaGenerata($generate);

        return $this->response->download('Proposte abbonamento ' . date('Y-m-d') . '.zip', $contenuto, true);
    }

    /**
     * Il nome del file nello zip, con « (2)», « (3)»… se è già stato usato: due abbonamenti
     * dello stesso cliente, anno e tipo (una proposta rifiutata e rifatta) avrebbero lo stesso
     * nome, e il secondo sovrascriverebbe il primo.
     */
    private function nomeLibero(string $nome, array &$usati): string
    {
        $base   = substr($nome, 0, -strlen('.docx'));
        $libero = $nome;

        for ($n = 2; isset($usati[mb_strtolower($libero)]); $n++) {
            $libero = "{$base} ({$n}).docx";
        }
        $usati[mb_strtolower($libero)] = true;

        return $libero;
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

    /**
     * Annulla l'accettazione: cancella gli interventi generati e riporta l'abbonamento a proposta.
     *
     * È l'inverso esatto di accetta(), unico punto in cui gli interventi nascono. Tenendo qui
     * l'unico punto in cui muoiono, vale l'invariante che regge tutto il resto: un abbonamento
     * in stato proposta non ha mai interventi collegati. Da lì lo si corregge e si riaccetta —
     * gli interventi si rigenerano dai periodi correnti, senza bisogno di un "rigenera" a parte —
     * oppure lo si elimina.
     *
     * Metodo separato da cambiaStato() per la stessa ragione di accetta(): è l'unica altra
     * transizione con un effetto massivo sulle righe figlie, e nasconderlo in un ramo di un
     * metodo pensato per cambi di stato leggeri lo renderebbe invisibile.
     */
    public function annullaAccettazione(int $id): RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->trovaConDettagli($id);

        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        $ammessi = [AbbonamentiModel::STATO_ATTIVO, AbbonamentiModel::STATO_SOSPESO];
        if (! in_array($abbonamento['stato_calcolato'], $ammessi, true)) {
            return redirect()->to('abbonamenti/' . $id)->with('error',
                'Si annulla l\'accettazione solo di un abbonamento attivo o sospeso: uno scaduto o disdetto è storia, non un errore da correggere.');
        }

        // Una query sola: le stesse righe servono per la guardia e per il conteggio.
        $interventi = (new InterventiModel())->perAbbonamento($id);
        $bloccanti  = array_values(array_filter(
            $interventi,
            static fn (array $i): bool => in_array($i['stato'], self::STATI_BLOCCANO_ANNULLAMENTO, true)
        ));

        if ($bloccanti !== []) {
            helper('interventi');

            // Etichetta col codice e lo stato: sono tutti dello stesso abbonamento e dello
            // stesso cliente, quindi il nome del cliente non distinguerebbe niente.
            $etichetta = static fn (array $i): string => $i['codice']
                . ' (' . (InterventiModel::STATI_LABEL[$i['stato']] ?? $i['stato']) . ')';

            $quanti = count($bloccanti);

            return redirect()->to('abbonamenti/' . $id)->with('error',
                'Non si può annullare l\'accettazione: ' . $quanti . ' intervent'
                . ($quanti === 1 ? 'o è già stato pianificato o lavorato' : 'i sono già stati pianificati o lavorati')
                . ' — ' . elenco_interventi_link($bloccanti, $etichetta)
                . ' Spostali o annullali prima di procedere.');
        }

        $db = db_connect();
        $db->transStart();

        $cancellati = (new InterventiModel())->eliminaPerAbbonamento($id);
        $model->update($id, ['stato' => AbbonamentiModel::STATO_PROPOSTA]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('abbonamenti/' . $id)->with('error',
                'Errore durante l\'annullamento: nessuna modifica è stata salvata.');
        }

        return redirect()->to('abbonamenti/' . $id)->with('success',
            'Accettazione annullata: ' . $cancellati . ' intervent' . ($cancellati === 1 ? 'o cancellato' : 'i cancellati')
            . '. L\'abbonamento è tornato in proposta e ora si può modificare o eliminare.');
    }

    /**
     * Elimina definitivamente un abbonamento in stato proposta, con i suoi periodi.
     *
     * Solo da proposta, dove per l'invariante di annullaAccettazione() non esistono interventi
     * collegati: non serve nessun controllo sui figli. I periodi seguono da soli, la loro
     * foreign key è ON DELETE CASCADE. Nessuna transazione: è una sola DELETE, e la cascata è
     * atomica dentro la stessa istruzione.
     */
    public function elimina(int $id): RedirectResponse
    {
        $model       = new AbbonamentiModel();
        $abbonamento = $model->trovaConDettagli($id);

        if (! $abbonamento) {
            return redirect()->to('abbonamenti')->with('error', 'Abbonamento non trovato.');
        }

        if ($abbonamento['stato_calcolato'] !== AbbonamentiModel::STATO_PROPOSTA) {
            return redirect()->to('abbonamenti/' . $id)->with('error',
                'Si elimina solo un abbonamento in stato proposta. Se è già stato accettato, annulla prima l\'accettazione.');
        }

        // La foreign key è ON DELETE RESTRICT dalla v0.29.0: senza questo controllo l'utente
        // vedrebbe un errore SQL invece di capire che deve prima occuparsi del rinnovo.
        if (! empty($abbonamento['successore_id'])) {
            return redirect()->to('abbonamenti/' . $id)->with('error',
                'Questo abbonamento è già stato rinnovato e non si può eliminare: '
                . '<a href="' . base_url('abbonamenti/' . $abbonamento['successore_id']) . '">apri il rinnovo</a>'
                . ' e occupati prima di quello.');
        }

        $model->delete($id);

        return redirect()->to('abbonamenti')->with('success', 'Abbonamento eliminato.');
    }

    /**
     * Regole comuni a store() e update(). Le apparecchiature sono obbligatorie solo per i tipi
     * della categoria addolcitori, dove sono la ragione stessa dell'abbonamento; per le piscine
     * l'impianto è la piscina, sottintesa.
     */
    private function regolaValidazione(): array
    {
        $regole = $this->regoleBase();

        $tipo = (new TipiInterventoModel())->find((int) $this->request->getPost('tipo_intervento_id'));
        if (($tipo['categoria'] ?? null) === TipiInterventoModel::CATEGORIA_ADDOLCITORI) {
            $regole['apparecchiature'] = [
                'rules'  => 'required',
                'errors' => ['required' => 'Per gli addolcitori va indicata almeno un\'apparecchiatura installata.'],
            ];
        }

        return $regole;
    }

    /**
     * Regole che non dipendono dal tipo di abbonamento scelto.
     */
    private function regoleBase(): array
    {
        return [
            'cliente_id'         => 'required|is_natural_no_zero',
            'tipo_intervento_id' => 'required|is_natural_no_zero',
            'data_inizio'        => 'required|valid_date[Y-m-d]',
            'data_fine'          => [
                'rules'  => 'required|valid_date[Y-m-d]|successiva_a[data_inizio]',
                'errors' => [
                    'successiva_a' => 'La data di fine deve essere successiva alla data di inizio: un abbonamento non può durare un giorno solo.',
                ],
            ],
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
            // Il jolly nel riferimento fa confrontare ogni riga con la propria data di inizio,
            // non con quella del primo periodo (vedi App\Validation\DateRules).
            'periodi.*.data_fine' => [
                'rules'  => 'required|valid_date[Y-m-d]|successiva_a[periodi.*.data_inizio]',
                'errors' => [
                    'required'     => 'Ogni periodo deve avere una data di fine.',
                    'valid_date'   => 'La data di fine di un periodo non è valida.',
                    'successiva_a' => 'La data di fine di un periodo deve essere successiva alla sua data di inizio.',
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
