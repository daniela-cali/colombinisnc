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

    const STATI_LABEL = [
        'attivo'   => 'Attivo',
        'sospeso'  => 'Sospeso',
        'scaduto'  => 'Scaduto',
        'disdetto' => 'Disdetto',
    ];

    const STATI_BADGE = [
        'attivo'   => 'bg-success',
        'sospeso'  => 'bg-warning text-dark',
        'scaduto'  => 'bg-secondary',
        'disdetto' => 'bg-danger',
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
     * Restituisce tutti gli abbonamenti di un cliente ordinati per data_inizio DESC.
     * Aggiunge num_periodi e prima_frequenza per la visualizzazione nelle liste.
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select([
                'abbonamenti.*',
                'ti.nome AS tipo_nome',
                "CASE WHEN abbonamenti.data_fine < CURDATE() AND abbonamenti.stato = 'attivo'
                      THEN 'scaduto' ELSE abbonamenti.stato END AS stato_calcolato",
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
                "CASE WHEN c.tipo = 'persona_fisica'
                      THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                      ELSE c.ragsoc
                 END AS cliente_denominazione",
                'ti.nome AS tipo_nome',
                "CASE WHEN abbonamenti.data_fine < CURDATE() AND abbonamenti.stato = 'attivo'
                      THEN 'scaduto' ELSE abbonamenti.stato END AS stato_calcolato",
                'EXISTS(SELECT 1 FROM abbonamenti a2 WHERE a2.abbonamento_precedente_id = abbonamenti.id) AS ha_successore',
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
                "CASE WHEN c.tipo = 'persona_fisica'
                      THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                      ELSE c.ragsoc
                 END AS cliente_denominazione",
                'ti.nome AS tipo_nome',
                "CASE WHEN abbonamenti.data_fine < CURDATE() AND abbonamenti.stato = 'attivo'
                      THEN 'scaduto' ELSE abbonamenti.stato END AS stato_calcolato",
                'EXISTS(SELECT 1 FROM abbonamenti a2 WHERE a2.abbonamento_precedente_id = abbonamenti.id) AS ha_successore',
                '(SELECT COUNT(*) FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id) AS num_periodi',
                '(SELECT ap.frequenza FROM abbonamenti_periodi ap WHERE ap.abbonamento_id = abbonamenti.id ORDER BY ap.ordine ASC LIMIT 1) AS prima_frequenza',
            ])
            ->join('clienti c',         'c.id  = abbonamenti.cliente_id',        'left')
            ->join('tipi_intervento ti', 'ti.id = abbonamenti.tipo_intervento_id', 'left')
            ->orderBy('abbonamenti.data_fine', 'DESC')
            ->findAll();
    }

    /**
     * Abbonamenti attivi in scadenza entro $giorni giorni, con denominazione cliente,
     * tipo intervento e giorni rimanenti. Ordinati per data di fine crescente (dashboard).
     */
    public function inScadenza(int $giorni = 30): array
    {
        return $this->select("abbonamenti.id, abbonamenti.data_fine,
                CASE WHEN clienti.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                     ELSE clienti.ragsoc
                END AS cliente_denominazione,
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
     * Genera in batch tutti gli interventi dai periodi dell'abbonamento e li inserisce in DB.
     * Va chiamato dentro una transazione nel controller: se un insert fallisce il rollback
     * annulla sia l'abbonamento che tutti gli interventi già inseriti.
     * Restituisce il numero di interventi creati.
     */
    public function generaInterventi(int $abbonamentoId, array $abbonamento): int
    {
        $periodi = (new AbbonamentiPeriodiModel())->perAbbonamento($abbonamentoId);

        if (empty($periodi)) {
            return 0;
        }

        $interventiModel = new InterventiModel();
        $count = 0;

        foreach ($periodi as $periodo) {
            $scadenze = $this->calcolaScadenzePeriodi(
                $periodo['data_inizio'],
                $periodo['data_fine'],
                $periodo['frequenza']
            );

            foreach ($scadenze as $scadenza) {
                $interventiModel->insert([
                    'cliente_id'         => $abbonamento['cliente_id'],
                    'abbonamento_id'     => $abbonamentoId,
                    'tipo_intervento_id' => $abbonamento['tipo_intervento_id'],
                    'priorita'           => InterventiModel::PRIORITA_ABBONAMENTO,
                    'stato'              => InterventiModel::STATO_DA_PIANIFICARE,
                    'data_pianificata'   => null,
                    'data_scadenza'      => $scadenza,
                    'pulizia_fondo'      => (int) ($periodo['con_pulizia_fondo'] ?? 0),
                    'descrizione'        => 'Visita in abbonamento [#' . $abbonamentoId ."]",
                ]);
                $count++;
            }
        }

        return $count;
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
     * Annulla definitivamente gli interventi futuri da_pianificare e sospesi.
     * Chiamato su disdetta, o quando l'utente rifiuta il ripristino dopo una sospensione.
     */
    public function annullaInterventi(int $abbonamentoId): void
    {
        (new InterventiModel())->annullaPerAbbonamento($abbonamentoId);
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
