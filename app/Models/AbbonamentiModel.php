<?php

namespace App\Models;

use CodeIgniter\Model;

class AbbonamentiModel extends Model
{
    protected $table         = 'abbonamenti';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'cliente_id', 'tipo_intervento_id', 'abbonamento_precedente_id',
        'data_inizio', 'data_fine', 'durata_mesi',
        'prezzo', 'stato', 'note',
        'operazioni_incluse', 'modalita_pagamento',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori frequenza
    const FREQUENZA_SETTIMANALE  = 'settimanale';
    const FREQUENZA_QUINDICINALE = 'quindicinale';
    const FREQUENZA_MENSILE      = 'mensile';
    const FREQUENZA_BIMESTRALE   = 'bimestrale';
    const FREQUENZA_TRIMESTRALE  = 'trimestrale';
    const FREQUENZA_SEMESTRALE   = 'semestrale';
    const FREQUENZA_ANNUALE      = 'annuale';

    const FREQUENZE_LABEL = [
        'settimanale'  => 'Settimanale',
        'quindicinale' => 'Quindicinale',
        'mensile'      => 'Mensile',
        'bimestrale'   => 'Bimestrale',
        'trimestrale'  => 'Trimestrale',
        'semestrale'   => 'Semestrale',
        'annuale'      => 'Annuale',
    ];

    // valori stato — 'scaduto' calcolato a runtime nelle query; può essere scritto su DB in futuro via cron
    const STATO_ATTIVO   = 'attivo';
    const STATO_SOSPESO  = 'sospeso';
    const STATO_SCADUTO  = 'scaduto';
    const STATO_DISDETTO = 'disdetto';
    const STATO_PROPOSTA  = 'proposta';  // nuovo — stato di partenza
    const STATO_RIFIUTATA = 'rifiutata'; // nuovo — terminale, il cliente non ha accettato

    /**
     * Stati che diventano 'scaduto' quando data_fine è passata.
     *
     * Punto unico della regola: la usano il frammento SQL di selectStatoCalcolato() e la
     * query del batch notturno leggiScaduti(). Un abbonamento sospeso scade come uno attivo —
     * la sospensione mette in pausa le visite, non allunga il contratto, e la sua data di fine
     * è per costruzione quella dell'ultimo periodo (vedi periodiCoprono() nel controller).
     */
    const STATI_SCADIBILI = [self::STATO_ATTIVO, self::STATO_SOSPESO];

    const STATI_LABEL = [
        'attivo'   => 'Attivo',
        'sospeso'  => 'Sospeso',
        'scaduto'  => 'Scaduto',
        'disdetto' => 'Disdetto',
        'proposta' => 'Proposta',
        'rifiutata'=> 'Rifiutata',
    ];

    const STATI_BADGE = [
        'attivo'    => 'bg-success',
        'sospeso'   => 'bg-warning text-dark',
        'scaduto'   => 'bg-secondary',
        'disdetto'  => 'bg-danger',
        'proposta'  =>  'bg-info text-dark',
        'rifiutata' => 'bg-danger',
    ];

    /**
     * Imposta created_by/updated_by e calcola durata_mesi automaticamente.
     * durata_mesi non va mai passato dal controller — è sempre derivato da data_inizio/data_fine.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['data_inizio'], $data['data']['data_fine'])) {
            $inizio = new \DateTime($data['data']['data_inizio']);
            $fine   = new \DateTime($data['data']['data_fine']);
            $diff   = $inizio->diff($fine);

            // DateInterval separa anni completi e mesi residui:
            // 01/01/2026 → 31/12/2026 = 0 anni + 11 mesi (non 1 anno, perché il 31/12 non completa 12 mesi esatti).
            // La formula converte tutto in mesi: es. 1 anno e 3 mesi → 1*12+3 = 15.
            // Per abbonamenti annuali tipici il risultato sarà 11 — campo solo informativo, l'approssimazione è accettabile.
            $data['data']['durata_mesi'] = $diff->y * 12 + $diff->m;
        }

        foreach (['abbonamento_precedente_id', 'prezzo', 'note'] as $campo) {
            if (array_key_exists($campo, $data['data']) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }

    /**
     * Frammento SELECT che calcola lo stato effettivo dell'abbonamento.
     *
     * 'scaduto' non è memorizzato nel momento in cui matura: il batch notturno
     * (batch:abbonamenti-scaduti) lo scrive una volta al giorno, quindi fra la data di fine e
     * l'esecuzione del batch lo stato salvato è ancora quello vecchio. Questo CASE colma la
     * finestra, e dal giorno dopo i due valori coincidono.
     *
     * Esisteva in quattro copie identiche sparse fra le query: qui in un punto solo, così la
     * regola e l'elenco STATI_SCADIBILI si modificano una volta sola.
     */
    private function selectStatoCalcolato(): string
    {
        $scadibili = implode(', ', array_map(fn (string $s) => $this->db->escape($s), self::STATI_SCADIBILI));
        $scaduto   = $this->db->escape(self::STATO_SCADUTO);

        return "CASE WHEN abbonamenti.data_fine < CURDATE() AND abbonamenti.stato IN ({$scadibili})
                      THEN {$scaduto} ELSE abbonamenti.stato END AS stato_calcolato";
    }

    /**
     * Un abbonamento è rinnovabile solo se tutte e quattro le condizioni valgono.
     *
     * Riceve una riga già letta da trovaConDettagli() o elencoConDettagli(), che portano
     * stato_calcolato e successore_id; non tocca il database. Serve alle due view per
     * decidere se mostrare il bottone e a rinnova() per rifiutare la richiesta, così bottone
     * e rotta non possono dire cose diverse — che è il difetto che index e scheda avevano
     * prima, offrendo il rinnovo su insiemi di stati differenti.
     */
    public function rinnovabile(array $abbonamento): bool
    {
        return $this->motivoNonRinnovabile($abbonamento) === null;
    }

    /**
     * Perché questo abbonamento non è rinnovabile, o null se lo è.
     *
     * Decisione e spiegazione stanno nello stesso metodo di proposito: se il controller
     * ricostruisse le condizioni per comporre il messaggio, prima o poi direbbe all'utente
     * un motivo diverso da quello per cui ha davvero rifiutato. Le frasi sono pensate per
     * essere incastrate dopo "Questo abbonamento ...".
     */
    public function motivoNonRinnovabile(array $abbonamento): ?string
    {
        $stato = $abbonamento['stato_calcolato'] ?? $abbonamento['stato'] ?? '';

        // Un contratto in pausa non è una base da cui proiettare l'anno successivo.
        if ($stato === self::STATO_SOSPESO) {
            return 'è sospeso: va riattivato prima di poterlo rinnovare';
        }

        // proposta e rifiutata: non è mai stato un contratto, non c'è niente da rinnovare.
        if (! in_array($stato, [self::STATO_ATTIVO, self::STATO_SCADUTO, self::STATO_DISDETTO], true)) {
            return 'non è mai stato accettato';
        }

        // Un solo rinnovo per abbonamento; il vincolo è anche strutturale
        // (uq_abbonamenti_abbonamento_precedente_id).
        if (! empty($abbonamento['successore_id'])) {
            return 'è già stato rinnovato';
        }

        // Il rinnovo va sempre fatto sul contratto in corso, che proietta all'anno dopo: si
        // prepara quanto si vuole in anticipo. Rinnovare uno che non è ancora cominciato
        // significherebbe invece saltare avanti di due anni — creato il 2028 nel 2027, lo si
        // rinnoverebbe subito ottenendo il 2029 con i suoi interventi già nel pool.
        // Creare un abbonamento con date future resta libero: il vincolo è solo sul rinnovo.
        if ($abbonamento['data_inizio'] > date('Y-m-d')) {
            return 'non è ancora cominciato: rinnova l\'abbonamento attualmente in corso, non quello futuro';
        }

        return null;
    }

    /**
     * Restituisce tutti gli abbonamenti di un cliente ordinati per data_inizio DESC.
     * Aggiunge num_periodi e prima_frequenza per la visualizzazione nelle liste.
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select([
                'abbonamenti.*',
                'ti.nome AS tipo_nome',
                $this->selectStatoCalcolato(),
                '(SELECT COUNT(*) FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id) AS num_periodi',
                '(SELECT ap.frequenza FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id ORDER BY ap.ordine ASC LIMIT 1) AS prima_frequenza',
            ])
            ->join('tipi_intervento ti', 'ti.id = abbonamenti.tipo_intervento_id', 'left')
            ->where('abbonamenti.cliente_id', $clienteId)
            ->orderBy('abbonamenti.data_inizio', 'DESC')
            ->findAll();
    }

    /**
     * Trova un singolo abbonamento con denominazione cliente, tipo e stato calcolato.
     * Restituisce null se non trovato.
     */
    public function trovaConDettagli(int $id): ?array
    {
        $result = $this->select([
                'abbonamenti.*',
                "c.denominazione AS cliente_denominazione",
                'ti.nome AS tipo_nome',
                $this->selectStatoCalcolato(),
                '(SELECT a2.id FROM abbonamenti a2 WHERE a2.abbonamento_precedente_id = abbonamenti.id LIMIT 1) AS successore_id',
                '(SELECT COUNT(*) FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id) AS num_periodi',
                '(SELECT ap.frequenza FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id ORDER BY ap.ordine ASC LIMIT 1) AS prima_frequenza',
            ])
            ->join('clienti c',         'c.id  = abbonamenti.cliente_id',        'left')
            ->join('tipi_intervento ti', 'ti.id = abbonamenti.tipo_intervento_id', 'left')
            ->find($id);

        return $result ?: null;
    }

    /**
     * Elenco globale con denominazione cliente, tipo intervento e stato calcolato.
     * ha_successore = 1 se esiste già un abbonamento di rinnovo che punta a questo.
     * Usato nell'index per decidere se mostrare il bottone Rinnova.
     */
    public function elencoConDettagli(): array
    {
        return $this->select([
                'abbonamenti.*',
                "c.denominazione AS cliente_denominazione",
                'ti.nome AS tipo_nome',
                $this->selectStatoCalcolato(),
                '(SELECT a2.id FROM abbonamenti a2 WHERE a2.abbonamento_precedente_id = abbonamenti.id LIMIT 1) AS successore_id',
                '(SELECT COUNT(*) FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id) AS num_periodi',
                '(SELECT ap.frequenza FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id ORDER BY ap.ordine ASC LIMIT 1) AS prima_frequenza',
                'SUBSTRING(abbonamenti.data_inizio, 1, 4) AS anno_inizio',
            ])
            ->join('clienti c',         'c.id  = abbonamenti.cliente_id',        'left')
            ->join('tipi_intervento ti', 'ti.id = abbonamenti.tipo_intervento_id', 'left')
            ->orderBy('abbonamenti.data_fine', 'DESC')
            ->findAll();
    }

    /**
     * Abbonamenti la cui data_fine è passata ma il cui stato è ancora fra gli STATI_SCADIBILI.
     *
     * Alimenta il batch notturno (comando batch:abbonamenti-scaduti), che li mostra e poi li
     * aggiorna con updateScaduti(). Confronta con la data calcolata da PHP e non con CURDATE(),
     * quindi non dipende dal fuso orario del server MySQL.
     */
    public function leggiScaduti(): array
    {
        return $this->select('abbonamenti.id, c.denominazione AS cliente_denominazione, ti.nome, abbonamenti.stato, abbonamenti.data_fine')
            ->join('tipi_intervento ti', 'abbonamenti.tipo_intervento_id = ti.id')
            ->join('clienti c', 'abbonamenti.cliente_id = c.id')
            ->where('abbonamenti.data_fine <', date('Y-m-d'))
            ->whereIn('abbonamenti.stato', self::STATI_SCADIBILI)
            ->findAll();
    }

    /**
     * Scrive stato = 'scaduto' sugli id passati e restituisce quante righe ha aggiornato.
     */
    public function updateScaduti(array $ids): int
    {
        $this->whereIn('id', $ids)->update(null, ['stato' => self::STATO_SCADUTO]);

        return $this->db->affectedRows();
    }

    /**
     * Abbonamenti attivi in scadenza entro $giorni giorni, con denominazione cliente,
     * tipo intervento e giorni rimanenti. Ordinati per data di fine crescente (dashboard).
     */
    public function inScadenza(int $giorni = 30): array
    {
        return $this->select("abbonamenti.id, abbonamenti.data_fine,
                clienti.denominazione AS cliente_denominazione,
                tipi_intervento.nome AS tipo,
                DATEDIFF(abbonamenti.data_fine, CURDATE()) AS giorni_rimasti")
            ->join('clienti', 'clienti.id = abbonamenti.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = abbonamenti.tipo_intervento_id', 'left')
            ->where('abbonamenti.stato', self::STATO_ATTIVO)
            ->where('abbonamenti.data_fine >=', date('Y-m-d'))
            ->where('abbonamenti.data_fine <=', date('Y-m-d', strtotime("+{$giorni} days")))
            ->orderBy('abbonamenti.data_fine', 'ASC')
            ->findAll();
    }

    /**
     * Genera in batch tutti gli interventi dai periodi dell'abbonamento e li inserisce in DB
     * con un'unica insertBatch(). Va chiamato dentro una transazione nel controller: se un
     * insert fallisce il rollback annulla sia l'abbonamento che tutti gli interventi già inseriti.
     *
     * insertBatch() non esegue i callback $beforeInsert del model (solo $useTimestamps
     * resta automatico) — normalizza() di InterventiModel non viene quindi chiamato: il
     * codice progressivo (generaCodice()) e created_by/updated_by sono replicati qui a mano,
     * riga per riga, prima della insertBatch() finale.
     *
     * Restituisce il numero di interventi creati.
     */
    public function generaInterventi(int $abbonamentoId, array $abbonamento): int
    {
        $periodi = (new AbbonamentiPeriodiModel())->perAbbonamento($abbonamentoId);

        if (empty($periodi)) {
            return 0;
        }

        $interventiModel = new InterventiModel();
        $tipo            = (new TipiInterventoModel())->find($abbonamento['tipo_intervento_id']);
        $prefisso        = $tipo['prefisso_codice'] ?? 'INT';
        $userId          = user_id();

        $righe          = [];
        $ultimaScadenza = null; // garantisce sequenza strettamente crescente tra periodi diversi

        foreach ($periodi as $periodo) {
            $scadenze = $this->calcolaScadenzePeriodi(
                $periodo['data_inizio'],
                $periodo['data_fine'],
                $periodo['frequenza']
            );

            foreach ($scadenze as $scadenza) {
                if ($ultimaScadenza !== null && $scadenza <= $ultimaScadenza) {
                    continue; // duplicato/sovrapposizione al confine tra periodi: scartato
                }

                $righe[] = [
                    'codice'             => $interventiModel->generaCodice($prefisso),
                    'cliente_id'         => $abbonamento['cliente_id'],
                    'abbonamento_id'     => $abbonamentoId,
                    'tipo_intervento_id' => $abbonamento['tipo_intervento_id'],
                    'priorita'           => InterventiModel::PRIORITA_ABBONAMENTO,
                    'stato'              => InterventiModel::STATO_DA_PIANIFICARE,
                    'data_pianificata'   => null,
                    'data_scadenza'      => $scadenza,
                    'pulizia_fondo'      => (int) ($periodo['con_pulizia_fondo'] ?? 0),
                    'descrizione'        => 'Visita in abbonamento [#' . $abbonamentoId . ']',
                    'created_by'         => $userId,
                    'updated_by'         => $userId,
                ];
                $ultimaScadenza = $scadenza;
            }
        }

        if (empty($righe)) {
            return 0;
        }

        $interventiModel->insertBatch($righe);

        return count($righe);
    }

    /**
     * Porta a 'sospeso' gli interventi futuri da_pianificare dell'abbonamento.
     * Chiamato quando l'abbonamento passa ad 'attivo' → 'sospeso'.
     */
    public function sospendiInterventi(int $abbonamentoId): void
    {
        (new InterventiModel())->sospendiPerAbbonamento($abbonamentoId);
    }

    /**
     * Riporta a 'da_pianificare' gli interventi sospesi (tornano nel pool del calendario).
     * Chiamato quando abbonamento torna 'attivo' e l'utente conferma il ripristino.
     */
    public function ripristinaInterventi(int $abbonamentoId): void
    {
        (new InterventiModel())->ripristinaPerAbbonamento($abbonamentoId);
    }

    /**
     * Annulla definitivamente gli interventi futuri dell'abbonamento.
     *
     * Su disdetta si passa $inclusiPianificati = true, così cadono anche le visite già in
     * calendario; sul rifiuto del ripristino dopo una sospensione no. Restituisce i conteggi
     * che servono al messaggio di ritorno — vedi InterventiModel::annullaPerAbbonamento().
     *
     * @return array{totale: int, pianificati: int}
     */
    public function annullaInterventi(int $abbonamentoId, bool $inclusiPianificati = false): array
    {
        return (new InterventiModel())->annullaPerAbbonamento($abbonamentoId, $inclusiPianificati);
    }

    /**
     * Calcola le date di fine sotto-periodo tra data_inizio e data_fine per la frequenza data.
     *
     * Tutte le frequenze si allineano ai confini naturali del calendario — mai "+N giorni secchi"
     * dall'inizio del contratto — così il filtro LAST_DAY(CURDATE()) sul pool funziona senza
     * bisogno di sapere la frequenza.
     *
     * Settimanale  → domenica della settimana ISO corrente, poi domeniche successive.
     * Quindicinale → 15 del mese (se inizio ≤ 15°) o fine mese (se inizio ≥ 16°), poi si alterna.
     * Mensile+     → fine del mese di competenza. Aritmetica su anno/mese espliciti per evitare
     *                il bug di overflow di PHP ("31 gen + 3 mesi" → 1 apr invece di 31 mar).
     *
     * In tutti i casi l'ultimo elemento è sempre data_fine del periodo.
     */
    private function calcolaScadenzePeriodi(string $dataInizio, string $dataFine, string $frequenza): array
    {
        $fine     = new \DateTime($dataFine);
        $scadenze = [];

        if ($frequenza === self::FREQUENZA_SETTIMANALE) {
            // Prima scadenza: domenica della settimana ISO in corso (N=1 lun … N=7 dom)
            $cursor    = new \DateTime($dataInizio);
            $toSunday  = (int) $cursor->format('N'); // 1–7
            $toSunday  = $toSunday === 7 ? 0 : 7 - $toSunday;
            if ($toSunday > 0) {
                $cursor->modify("+{$toSunday} days");
            }
            while ($cursor < $fine) {
                $scadenze[] = $cursor->format('Y-m-d');
                $cursor->modify('+7 days');
            }
            $scadenze[] = $fine->format('Y-m-d');

        } elseif ($frequenza === self::FREQUENZA_QUINDICINALE) {
            // Prima scadenza: allinea alla quindicina naturale del mese
            // giorni 1–15 → scadenza il 15; giorni 16–31 → scadenza fine mese
            $start = new \DateTime($dataInizio);
            $day   = (int) $start->format('j');
            $y     = (int) $start->format('Y');
            $m     = (int) $start->format('n');

            if ($day <= 15) {
                $cursor = new \DateTime(sprintf('%04d-%02d-15', $y, $m));
            } else {
                $cursor = (new \DateTime(sprintf('%04d-%02d-01', $y, $m)))->modify('last day of this month');
            }

            while ($cursor < $fine) {
                $scadenze[] = $cursor->format('Y-m-d');
                $curDay = (int) $cursor->format('j');
                $curY   = (int) $cursor->format('Y');
                $curM   = (int) $cursor->format('n');
                if ($curDay === 15) {
                    // Dal 15 → fine dello stesso mese
                    $cursor = (new \DateTime(sprintf('%04d-%02d-01', $curY, $curM)))->modify('last day of this month');
                } else {
                    // Fine mese → 15 del mese successivo
                    $nm = $curM + 1;
                    $ny = $curY;
                    if ($nm > 12) { $nm = 1; $ny++; }
                    $cursor = new \DateTime(sprintf('%04d-%02d-15', $ny, $nm));
                }
            }
            $scadenze[] = $fine->format('Y-m-d');

        } else {
            // Mensile / bimestrale / trimestrale / semestrale / annuale
            $mesiPasso = [
                self::FREQUENZA_MENSILE     => 1,
                self::FREQUENZA_BIMESTRALE  => 2,
                self::FREQUENZA_TRIMESTRALE => 3,
                self::FREQUENZA_SEMESTRALE  => 6,
                self::FREQUENZA_ANNUALE     => 12,
            ];

            $passo = $mesiPasso[$frequenza] ?? 1;
            $start = new \DateTime($dataInizio);
            $anno  = (int) $start->format('Y');
            $mese  = (int) $start->format('n') + ($passo - 1);
            while ($mese > 12) { $mese -= 12; $anno++; }

            while (true) {
                $ultimoGiorno = (new \DateTime(sprintf('%04d-%02d-01', $anno, $mese)))
                    ->modify('last day of this month');

                if ($ultimoGiorno >= $fine) {
                    $scadenze[] = $fine->format('Y-m-d');
                    break;
                }

                $scadenze[] = $ultimoGiorno->format('Y-m-d');
                $mese += $passo;
                while ($mese > 12) { $mese -= 12; $anno++; }
            }
        }

        return $scadenze;
    }

}
