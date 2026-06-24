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
        'codice', 'cliente_id', 'tecnico_id', 'abbonamento_id',
        'extra', 'pulizia_fondo',
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
        $userId = session()->get('user_id');

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

        $nullabili = ['tecnico_id', 'tipo_intervento_id', 'impianto_id', 'data_pianificata', 'data_scadenza', 'durata_stimata'];
        foreach ($nullabili as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        // Il checkbox urgenza arriva come "1" se spuntato, assente se non spuntato
        if (isset($data['data']['urgenza'])) {
            $data['data']['urgenza'] = (int) $data['data']['urgenza'];
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
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select("interventi.*,
                ti.nome AS tipo_intervento_nome,
                ti.icona AS tipo_intervento_icona,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                (SELECT COUNT(*) FROM interventi_materiali im
                 WHERE im.intervento_id = interventi.id AND im.stato = 'da_portare') AS num_da_portare")
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->join('personale p',        'p.id = interventi.tecnico_id',          'left')
            ->where('interventi.cliente_id', $clienteId)
            ->orderBy('interventi.data_pianificata', 'DESC')
            ->findAll();
    }

    /**
     * Lista completa con denominazione cliente, tipo lavoro e nome tecnico.
     */
    public function elencoCompleto(): array
    {
        return $this->select("interventi.*,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione,
                ti.nome  AS tipo_intervento_nome,
                ti.icona AS tipo_intervento_icona,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome")
            ->join('clienti c',          'c.id  = interventi.cliente_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id',  'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',          'left')
            ->orderBy('interventi.data_pianificata', 'DESC')
            ->findAll();
    }

    /**
     * Interventi da pianificare per il pool del calendario.
     * Interventi normali: sempre inclusi se da_pianificare.
     * Interventi da abbonamento: solo quelli la cui data_scadenza cade nel mese corrente o passato,
     * così ogni frequenza (settimanale, mensile, trimestrale…) appare nel pool solo quando è il
     * momento di pianificarla — senza configurazioni arbitrarie né JOIN extra.
     */
    public function poolDaPianificare(): array
    {
        return $this->select("interventi.id, interventi.tipo_intervento_id, interventi.priorita,
                      interventi.urgenza, interventi.extra, interventi.data_scadenza, interventi.durata_stimata,
                      interventi.descrizione,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione,
                      c.citta AS cliente_citta,
                      c.zona  AS cliente_zona,
                      c.distanza_sede")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->where('interventi.stato', self::STATO_DA_PIANIFICARE)
            ->groupStart()
                ->where('interventi.abbonamento_id IS NULL', null, false)
                ->orWhere('interventi.extra', 1)
                ->orGroupStart()
                    ->where('interventi.abbonamento_id IS NOT NULL', null, false)
                    ->where('interventi.extra', 0)
                    ->where('interventi.data_scadenza <= LAST_DAY(CURDATE())', null, false)
                ->groupEnd()
            ->groupEnd()
            ->findAll();
    }

    /**
     * Scadenze aperte per la barra promemoria del calendario.
     * Stessa logica del pool: gli interventi da abbonamento appaiono solo nel mese di scadenza.
     */
    public function scadenzeAperte(): array
    {
        return $this->select("interventi.id, interventi.data_scadenza,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->where('interventi.data_scadenza IS NOT NULL', null, false)
            ->where('interventi.stato !=', self::STATO_COMPLETATO)
            ->where('interventi.stato !=', self::STATO_ANNULLATO)
            ->groupStart()
                ->where('interventi.abbonamento_id IS NULL', null, false)
                ->orGroupStart()
                    ->where('interventi.abbonamento_id IS NOT NULL', null, false)
                    ->where('interventi.data_scadenza <= LAST_DAY(CURDATE())', null, false)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('interventi.data_scadenza', 'ASC')
            ->findAll();
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
}
