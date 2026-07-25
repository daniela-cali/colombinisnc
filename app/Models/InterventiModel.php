<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\TipiInterventoModel;

class InterventiModel extends Model
{
    protected $table         = 'interventi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'codice', 'cliente_id', 'tecnico_id', 'abbonamento_id', 'cantiere_id',
        'extra', 'pulizia_fondo', 'apertura', 'chiusura',
        'priorita', 'stato', 'tipo_intervento_id',
        'data_pianificata', 'data_scadenza', 'durata_stimata', 'urgenza',
        'descrizione', 'impianto_id', 'note',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: abbonamento, normale, urgente
    const PRIORITA_ABBONAMENTO = 'abbonamento';
    const PRIORITA_NORMALE     = 'normale';
    const PRIORITA_URGENTE     = 'urgente';

    const PRIORITA_LABEL = [
        'abbonamento' => 'Abbonamento',
        'normale'     => 'Normale',
        'urgente'     => 'Urgente',
    ];

    // valori: da_pianificare, pianificato, in_corso, completato, annullato, sospeso
    const STATO_DA_PIANIFICARE = 'da_pianificare';
    const STATO_PIANIFICATO    = 'pianificato';
    const STATO_IN_CORSO       = 'in_corso';
    const STATO_COMPLETATO     = 'completato';
    const STATO_ANNULLATO      = 'annullato';
    const STATO_SOSPESO        = 'sospeso'; // in pausa per abbonamento sospeso — potenzialmente recuperabile

    const STATI_LABEL = [
        'da_pianificare' => 'Da pianificare',
        'pianificato'    => 'Pianificato',
        'in_corso'       => 'In corso',
        'completato'     => 'Completato',
        'annullato'      => 'Annullato',
        'sospeso'        => 'Sospeso',
    ];

    const STATI_BADGE = [
        'da_pianificare' => 'bg-light text-dark border',
        'pianificato'    => 'bg-info text-dark',
        'in_corso'       => 'bg-primary',
        'completato'     => 'bg-success',
        'annullato'      => 'bg-danger',
        'sospeso'        => 'bg-warning text-dark',
    ];

    /**
     * Imposta created_by/updated_by, nullifica i campi opzionali vuoti e normalizza urgenza.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        // array_key_exists distingue insert (nessuna chiave 'id') da update (chiave 'id' presente,
        // anche se null nei bulk update senza id — caso in cui isset() restituisce erroneamente false)
        if (! array_key_exists('id', $data)) {
            $data['data']['created_by'] = $userId;
            if (empty($data['data']['codice'])) {
                // Interventi da abbonamento usano il prefisso del tipo intervento (es. PIS, ADD).
                // Interventi manuali usano il fallback INT.
                $prefisso = 'INT';
                if (! empty($data['data']['extra'])) {
                    $prefisso = 'EXT';
                } elseif (! empty($data['data']['abbonamento_id']) && ! empty($data['data']['tipo_intervento_id'])) {
                    $tipo = (new TipiInterventoModel())->find((int) $data['data']['tipo_intervento_id']);
                    if (! empty($tipo['prefisso_codice'])) {
                        $prefisso = $tipo['prefisso_codice'];
                    }
                }
                $data['data']['codice'] = $this->generaCodice($prefisso);
            }
        }
        $data['data']['updated_by'] = $userId;

        // datetime-local invia 'YYYY-MM-DDTHH:MM' — converte a 'YYYY-MM-DD HH:MM:SS' per MySQL
        if (! empty($data['data']['data_pianificata']) && str_contains($data['data']['data_pianificata'], 'T')) {
            $data['data']['data_pianificata'] = date('Y-m-d H:i:s', strtotime($data['data']['data_pianificata']));
        }

        $nullabili = ['tecnico_id', 'tipo_intervento_id', 'impianto_id', 'cantiere_id', 'data_pianificata', 'data_scadenza', 'durata_stimata'];
        foreach ($nullabili as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        // Il checkbox urgenza arriva come "1" se spuntato, assente se non spuntato
        if (isset($data['data']['urgenza'])) {
            $data['data']['urgenza'] = (int) $data['data']['urgenza'];
        }

        // apertura/chiusura: flag booleani mutuamente esclusivi (un intervento non è
        // insieme apertura e chiusura). Cast a intero; se per errore arrivano entrambi a 1
        // l'apertura prevale. Il blocco si attiva solo se almeno uno dei due è presente,
        // così i bulk update che non li toccano restano invariati.
        if (array_key_exists('apertura', $data['data']) || array_key_exists('chiusura', $data['data'])) {
            $apertura = (int) (bool) ($data['data']['apertura'] ?? 0);
            $chiusura = (int) (bool) ($data['data']['chiusura'] ?? 0);
            $data['data']['apertura'] = $apertura;
            $data['data']['chiusura'] = $apertura ? 0 : $chiusura;
        }

        return $data;
    }

    /**
     * Genera il prossimo codice PREFISSO-XXXX per un nuovo intervento.
     *
     * Il prefisso viene passato dal chiamante:
     * - interventi manuali → 'INT' (default)
     * - interventi da abbonamento → prefisso_codice del tipo intervento (es. 'PIS', 'ADD')
     *
     * Usiamo un contatore atomico in settings anziché MAX(codice) o AUTO_INCREMENT.
     *
     * MAX(codice) regredisce dopo una cancellazione: se elimini INT-0010, il prossimo
     * sarebbe di nuovo INT-0010. AUTO_INCREMENT letto da information_schema è inaffidabile
     * perché InnoDB aggiorna quella vista in modo asincrono (può essere stale).
     *
     * Ogni prefisso ha la propria riga in settings(class='Interventi', key='seq_INT'),
     * 'seq_PIS', 'seq_ADD', ecc. Se la riga non esiste viene creata on-the-fly al primo
     * utilizzo del prefisso — nessuna migrazione manuale necessaria per ogni nuovo tipo.
     *
     * SELECT FOR UPDATE blocca la riga per tutta la durata della transazione: se due utenti
     * generano un codice contemporaneamente il secondo aspetta che il primo abbia già
     * incrementato e salvato — nessun duplicato possibile per righe esistenti.
     * Per righe nuove (primo uso di un prefisso) la finestra di race è trascurabile
     * in pratica: il primo abbonamento di un tipo viene creato una sola volta.
     */
    public function generaCodice(string $prefisso = 'INT'): string
    {
        $prefisso = strtoupper(trim($prefisso)) ?: 'INT';
        $key      = 'seq_' . $prefisso;
        $db       = $this->db;

        $db->transStart();

        $row = $db->query(
            "SELECT value FROM settings WHERE class = 'Interventi' AND `key` = ? FOR UPDATE",
            [$key]
        )->getRowArray();

        $numero = (int) ($row['value'] ?? 0) + 1;

        if ($row) {
            $db->query(
                "UPDATE settings SET value = ?, updated_at = NOW() WHERE class = 'Interventi' AND `key` = ?",
                [$numero, $key]
            );
        } else {
            $db->query(
                "INSERT INTO settings (class, `key`, value, created_at, updated_at) VALUES ('Interventi', ?, ?, NOW(), NOW())",
                [$key, $numero]
            );
        }

        $db->transComplete();

        return $prefisso . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lista interventi di un cliente con tipo lavoro e tecnico, ordinata per data decrescente.
     * Include anche gli interventi agganciati a un cantiere (con titolo del cantiere per il
     * badge in vista) — prima erano esclusi per non affollare la lista, ma questo li rendeva
     * invisibili dalla scheda cliente non appena uscivano dallo stato "da pianificare"
     * (l'unico contato nel badge della sezione Cantieri).
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select("interventi.*,
                ti.nome AS tipo_intervento_nome,
                ti.icona AS tipo_intervento_icona,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                ct.titolo AS cantiere_titolo,
                (SELECT COUNT(*) FROM interventi_materiali im
                 WHERE im.intervento_id = interventi.id AND im.stato = 'da_portare') AS num_da_portare")
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->join('personale p',        'p.id = interventi.tecnico_id',          'left')
            ->join('cantieri ct',        'ct.id = interventi.cantiere_id',        'left')
            ->where('interventi.cliente_id', $clienteId)
            ->orderBy('interventi.data_pianificata', 'DESC')
            ->findAll();
    }

    /**
     * Interventi collegati a un cantiere, con tipo lavoro e tecnico, ordinati per data decrescente.
     */
    public function perCantiere(int $cantiereId): array
    {
        return $this->select("interventi.*,
                ti.nome AS tipo_intervento_nome,
                ti.icona AS tipo_intervento_icona,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome")
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->join('personale p',        'p.id = interventi.tecnico_id',          'left')
            ->where('interventi.cantiere_id', $cantiereId)
            ->orderBy('interventi.data_pianificata', 'DESC')
            ->findAll();
    }

    /**
     * Lista completa con denominazione cliente, tipo lavoro e nome tecnico.
     */
    public function elencoCompleto(?string $categoria = null): array
    {
        $builder = $this->select("interventi.*,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione,
                ti.nome      AS tipo_intervento_nome,
                ti.icona     AS tipo_intervento_icona,
                ti.categoria AS tipo_intervento_categoria,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome")
            ->join('clienti c',          'c.id  = interventi.cliente_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id',  'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',          'left');

        // La sezione "generale" raccoglie anche gli interventi senza tipo (categoria NULL),
        // così nessun intervento resta fuori da tutte le liste.
        if ($categoria === TipiInterventoModel::CATEGORIA_GENERALE) {
            $builder->groupStart()
                    ->where('ti.categoria', TipiInterventoModel::CATEGORIA_GENERALE)
                    ->orWhere('interventi.tipo_intervento_id', null)
                    ->groupEnd();
        } elseif ($categoria !== null) {
            $builder->where('ti.categoria', $categoria);
        }

        return $builder->orderBy('interventi.data_pianificata', 'DESC')->findAll();
    }

    /**
     * Interventi da pianificare per il pool del calendario.
     * Interventi normali e visite extra: sempre inclusi se da_pianificare, indipendentemente da $finePeriodo.
     * Interventi da abbonamento: solo quelli la cui data_scadenza cade entro $finePeriodo (limite superiore,
     * nessun limite inferiore) — così ogni frequenza appare nel pool solo quando si avvicina al periodo
     * che il chiamante considera "visibile" (settimana/giorno sul calendario), senza nascondere gli arretrati.
     */
    public function poolDaPianificare(string $finePeriodo): array
    {
        return $this->select("interventi.id, interventi.tipo_intervento_id, interventi.priorita,
                      interventi.urgenza, interventi.extra, interventi.stato, interventi.data_scadenza, interventi.durata_stimata,
                      interventi.descrizione, interventi.cantiere_id, interventi.abbonamento_id, interventi.created_at,
                      interventi.tecnico_id,
                      TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione,
                      c.citta AS cliente_citta,
                      c.zona  AS cliente_zona,
                      c.distanza_sede")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->join('personale p', 'p.id = interventi.tecnico_id', 'left')
            ->where('interventi.stato', self::STATO_DA_PIANIFICARE)
            ->groupStart()
                ->where('interventi.abbonamento_id IS NULL', null, false)
                ->orWhere('interventi.extra', 1)
                ->orGroupStart()
                    ->where('interventi.abbonamento_id IS NOT NULL', null, false)
                    ->where('interventi.extra', 0)
                    ->where('interventi.data_scadenza <=', $finePeriodo)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('interventi.urgenza', 'DESC')
            ->orderBy('interventi.created_at', 'ASC')
            ->findAll();
    }

    /**
     * Scadenze in ritardo per la barra avviso del calendario: appuntamenti mancati
     * (pianificati con data ormai passata e mai completati), scadenze superate e
     * interventi da pianificare fermi da più di 7 giorni. Aggiunge a ogni riga
     * 'motivo' (mancato/ritardo/fermo, in ordine di gravità) e 'giorni', calcolati
     * in PHP dopo il fetch e usati per ordinare (mancati/ritardo prima, dal più
     * vecchio) e per i tooltip lato view.
     */
    public function scadenzeInRitardo(): array
    {
        $righe = $this->select("interventi.id, interventi.data_scadenza, interventi.stato,
                      interventi.data_pianificata, interventi.created_at,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->where('interventi.stato !=', self::STATO_COMPLETATO)
            ->where('interventi.stato !=', self::STATO_ANNULLATO)
            ->groupStart()
                ->groupStart()
                    ->where('interventi.stato', self::STATO_DA_PIANIFICARE)
                    // Il criterio "fermo" (created_at) esclude gli abbonamenti generati in
                    // blocco con scadenza oltre il mese corrente: created_at è sempre vecchio
                    // per costruzione, ma non sono davvero fermi finché non è il loro mese.
                    ->where('(interventi.data_scadenza < CURDATE()
                              OR (interventi.created_at <= CURDATE() - INTERVAL 7 DAY
                                  AND (interventi.abbonamento_id IS NULL OR interventi.data_scadenza <= LAST_DAY(CURDATE()))))', null, false)
                ->groupEnd()
                ->orGroupStart()
                    ->whereIn('interventi.stato', [self::STATO_PIANIFICATO, self::STATO_IN_CORSO])
                    ->where('(interventi.data_scadenza < CURDATE() OR interventi.data_pianificata < CURDATE())', null, false)
                ->groupEnd()
            ->groupEnd()
            ->findAll();

        $oggi = new \DateTime('today');
        foreach ($righe as &$r) {
            if (in_array($r['stato'], [self::STATO_PIANIFICATO, self::STATO_IN_CORSO], true)
                && $r['data_pianificata'] && substr($r['data_pianificata'], 0, 10) < $oggi->format('Y-m-d')) {
                $r['motivo'] = 'mancato';
                $r['giorni'] = $oggi->diff(new \DateTime($r['data_pianificata']))->days;
            } elseif ($r['data_scadenza'] && $r['data_scadenza'] < $oggi->format('Y-m-d')) {
                $r['motivo'] = 'ritardo';
                $r['giorni'] = $oggi->diff(new \DateTime($r['data_scadenza']))->days;
            } else {
                $r['motivo'] = 'fermo';
                $r['giorni'] = $oggi->diff(new \DateTime($r['created_at']))->days;
            }
        }
        unset($r);

        usort($righe, function (array $a, array $b): int {
            $peso = ['mancato' => 0, 'ritardo' => 0, 'fermo' => 1];
            $pa   = $peso[$a['motivo']];
            $pb   = $peso[$b['motivo']];
            return $pa !== $pb ? $pa <=> $pb : $b['giorni'] <=> $a['giorni'];
        });

        return $righe;
    }

    /**
     * Porta a 'sospeso' gli interventi futuri da_pianificare di un abbonamento.
     * Chiamato quando l'abbonamento passa ad 'attivo' → 'sospeso'.
     */
    public function sospendiPerAbbonamento(int $abbonamentoId): void
    {
        $this->where('abbonamento_id', $abbonamentoId)
             ->where('stato', self::STATO_DA_PIANIFICARE)
             ->where('data_scadenza >', date('Y-m-d'))
             ->set('stato', self::STATO_SOSPESO)
             ->update();
    }

    /**
     * Riporta a 'da_pianificare' gli interventi sospesi di un abbonamento (tornano nel pool).
     * Chiamato quando abbonamento torna 'attivo' e l'utente conferma il ripristino.
     */
    public function ripristinaPerAbbonamento(int $abbonamentoId): void
    {
        $this->where('abbonamento_id', $abbonamentoId)
             ->where('stato', self::STATO_SOSPESO)
             ->where('data_scadenza >', date('Y-m-d'))
             ->set('stato', self::STATO_DA_PIANIFICARE)
             ->update();
    }

    /**
     * Annulla definitivamente gli interventi futuri da_pianificare e sospesi di un abbonamento.
     * Chiamato su disdetta, o quando l'utente rifiuta il ripristino dopo una sospensione.
     */
    public function annullaPerAbbonamento(int $abbonamentoId): void
    {
        $this->whereIn('stato', [self::STATO_DA_PIANIFICARE, self::STATO_SOSPESO])
             ->where('abbonamento_id', $abbonamentoId)
             ->where('data_scadenza >', date('Y-m-d'))
             ->set('stato', self::STATO_ANNULLATO)
             ->update();
    }

    /**
     * Trova il prossimo intervento da abbonamento con data_scadenza successiva a quella data.
     * Usato in chiudi() per riassegnare i materiali non consegnati invece di lasciarli sospesi.
     */
    public function prossimoPerAbbonamento(int $abbonamentoId, string $dataScadenza): ?array
    {
        return $this->where('abbonamento_id', $abbonamentoId)
                    ->where('priorita', self::PRIORITA_ABBONAMENTO)
                    ->where('data_scadenza >', $dataScadenza)
                    ->orderBy('data_scadenza', 'ASC')
                    ->first();
    }

    /**
     * Prossimi interventi da abbonamento con scadenza successiva a quella data.
     * A differenza di prossimoPerAbbonamento() restituisce fino a $limit righe: serve
     * a rilevare l'ambiguità (due candidati con la stessa scadenza) prima di riassegnare
     * automaticamente i materiali non consegnati.
     */
    public function prossimiPerAbbonamento(int $abbonamentoId, string $dataScadenza, int $limit = 2): array
    {
        return $this->where('abbonamento_id', $abbonamentoId)
                    ->where('priorita', self::PRIORITA_ABBONAMENTO)
                    ->where('data_scadenza >', $dataScadenza)
                    ->orderBy('data_scadenza', 'ASC')
                    ->findAll($limit);
    }

    /**
     * Interventi pianificati in una data, con denominazione cliente, tipo e tecnico.
     * Passando $tecnicoId limita a quel tecnico (agenda personale della dashboard).
     */
    public function agendaGiorno(string $data, ?int $tecnicoId = null): array
    {
        $this->select("interventi.id, interventi.data_pianificata,
                CASE WHEN clienti.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                     ELSE clienti.ragsoc
                END AS cliente_denominazione,
                clienti.citta, clienti.indirizzo,
                tipi_intervento.nome AS tipo,
                TRIM(CONCAT_WS(' ', personale.cognome, personale.nome)) AS tecnico")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->join('personale', 'personale.id = interventi.tecnico_id', 'left')
            ->where('DATE(interventi.data_pianificata)', $data)
            ->where('interventi.stato', self::STATO_PIANIFICATO);

        if ($tecnicoId) {
            $this->where('interventi.tecnico_id', $tecnicoId);
        }

        return $this->orderBy('interventi.data_pianificata', 'ASC')->findAll();
    }

    /**
     * Interventi urgenti ancora da pianificare, con denominazione cliente e tipo.
     * $tecnicoId limita al singolo tecnico; $limit=0 significa nessun limite.
     */
    public function urgentiDaPianificare(?int $tecnicoId = null, int $limit = 0): array
    {
        $this->select("interventi.id,
                CASE WHEN clienti.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                     ELSE clienti.ragsoc
                END AS cliente_denominazione,
                clienti.citta, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.urgenza', 1)
            ->where('interventi.stato', self::STATO_DA_PIANIFICARE);

        if ($tecnicoId) {
            $this->where('interventi.tecnico_id', $tecnicoId);
        }

        return $this->orderBy('interventi.data_scadenza', 'ASC')->findAll($limit);
    }

    /**
     * Interventi attivi (pianificati o in corso) di un tecnico in una finestra di date,
     * con coordinate per la mappa dell'agenda mobile. Se l'intervento è legato a un
     * cantiere con luogo/posizione propri (vedi docs/spec/cantieri_luogo_referente_spec.md),
     * questi hanno priorità su quelli del cliente — altrimenti il tecnico verrebbe mandato
     * all'indirizzo del cliente anche quando il cantiere è altrove.
     */
    public function agendaTecnicoPeriodo(int $tecnicoId, string $dataInizio, string $dataFine): array
    {
        return $this->select("interventi.id, interventi.data_pianificata, interventi.stato,
                CASE WHEN clienti.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                     ELSE clienti.ragsoc
                END AS cliente_denominazione,
                COALESCE(cantieri.indirizzo, clienti.indirizzo) AS indirizzo,
                COALESCE(cantieri.citta, clienti.citta) AS citta,
                clienti.cap,
                COALESCE(cantieri.lat, clienti.lat) AS lat,
                COALESCE(cantieri.lng, clienti.lng) AS lng,
                tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('cantieri', 'cantieri.id = interventi.cantiere_id', 'left')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.tecnico_id', $tecnicoId)
            ->whereIn('interventi.stato', [self::STATO_PIANIFICATO, self::STATO_IN_CORSO])
            ->where('DATE(interventi.data_pianificata) >=', $dataInizio)
            ->where('DATE(interventi.data_pianificata) <=', $dataFine)
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();
    }

    /**
     * Durata in minuti di un intervento: quella stimata se presente, altrimenti il default
     * del tipo di intervento, con un minimo di 60'. Centralizza la formula usata anche da
     * eventiCalendario() e dal calcolo dell'orario suggerito, per non doverla mantenere in due posti.
     */
    public function durataMinuti(?int $durataStimata, ?int $durataDefaultTipo): int
    {
        return max(60, $durataStimata ?: $durataDefaultTipo ?: 60);
    }

    /**
     * Interventi pianificati/in corso di un tecnico in una singola data, con la durata
     * (stimata o default del tipo) — usato per calcolare l'orario suggerito nel modal
     * di pianificazione dal pool del calendario.
     */
    public function agendaGiornoTecnico(int $tecnicoId, string $data): array
    {
        return $this->select('interventi.data_pianificata, interventi.durata_stimata,
                ti.durata_default AS tipo_durata')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.tecnico_id', $tecnicoId)
            ->whereIn('interventi.stato', [self::STATO_PIANIFICATO, self::STATO_IN_CORSO])
            ->where('DATE(interventi.data_pianificata)', $data)
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();
    }

    /**
     * Interventi pianificati/in corso il cui tecnico risulta assente nella data pianificata —
     * nascono quando un'assenza viene inserita dopo che l'intervento era già stato pianificato.
     */
    public function inConflittoConAssenze(): array
    {
        return $this->select("interventi.id, interventi.data_pianificata,
                CASE WHEN clienti.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                     ELSE clienti.ragsoc
                END AS cliente_denominazione,
                TRIM(CONCAT_WS(' ', personale.cognome, personale.nome)) AS tecnico,
                assenze.tipo AS assenza_tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('personale', 'personale.id = interventi.tecnico_id')
            ->join('assenze', "assenze.personale_id = interventi.tecnico_id
                AND DATE(interventi.data_pianificata) >= assenze.data_inizio
                AND DATE(interventi.data_pianificata) <= assenze.data_fine")
            ->whereIn('interventi.stato', [self::STATO_PIANIFICATO, self::STATO_IN_CORSO])
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();
    }

    /**
     * Interventi di una giornata (tutti gli stati tranne annullato) per il foglio viaggio,
     * con denominazione, indirizzo e zona del cliente, tecnico e tipo lavoro.
     * $tecnicoId, se valorizzato, limita il risultato al singolo tecnico (foglio viaggio individuale).
     */
    public function perGiornata(string $data, ?int $tecnicoId = null): array
    {
        $query = $this->select("interventi.id, interventi.data_pianificata, interventi.durata_stimata,
                interventi.descrizione, interventi.urgenza, interventi.priorita, interventi.tecnico_id,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione,
                c.indirizzo, c.citta, c.zona AS cliente_zona,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                ti.nome AS tipo_nome, ti.icona AS tipo_icona")
            ->join('clienti c',          'c.id  = interventi.cliente_id',         'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->where('DATE(interventi.data_pianificata)', $data)
            ->where('interventi.stato !=', self::STATO_ANNULLATO);

        if ($tecnicoId !== null) {
            $query->where('interventi.tecnico_id', $tecnicoId);
        }

        return $query->orderBy('interventi.data_pianificata', 'ASC')->findAll();
    }

    /**
     * Interventi pianificati in un range di date per il calendario (FullCalendar).
     * Esclude gli annullati e quelli senza data. $tecnicoId filtra sul singolo tecnico.
     */
    public function eventiCalendario(string $start, string $end, ?int $tecnicoId = null): array
    {
        $this->select("interventi.id, interventi.stato, interventi.data_pianificata,
                interventi.durata_stimata, interventi.descrizione, interventi.data_scadenza,
                interventi.created_at,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione,
                c.citta AS cliente_citta,
                p.nome AS tecnico_nome, p.cognome AS tecnico_cognome, p.colore AS tecnico_colore,
                ti.durata_default AS tipo_durata, ti.nome AS tipo_nome, ti.icona AS tipo_icona")
            ->join('clienti c',          'c.id  = interventi.cliente_id',         'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.data_pianificata >=', $start)
            ->where('interventi.data_pianificata <',  $end)
            ->where('interventi.data_pianificata IS NOT NULL', null, false)
            ->where('interventi.stato !=', self::STATO_ANNULLATO)
            ->orderBy('interventi.created_at');

        if ($tecnicoId) {
            $this->where('interventi.tecnico_id', $tecnicoId);
        }

        return $this->findAll();
    }

    /**
     * Interventi figli di un abbonamento, ordinati per data di scadenza.
     */
    public function perAbbonamento(int $abbonamentoId): array
    {
        return $this->select('id, codice, data_scadenza, data_pianificata, stato, extra')
            ->where('abbonamento_id', $abbonamentoId)
            ->orderBy('data_scadenza', 'ASC')
            ->findAll();
    }

    /**
     * Numero di interventi collegati a un tipo intervento (blocco cancellazione).
     */
    public function contaPerTipo(int $tipoId): int
    {
        return $this->where('tipo_intervento_id', $tipoId)->countAllResults();
    }
}
