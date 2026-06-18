<?php

namespace App\Models;

use CodeIgniter\Model;

class InterventiModel extends Model
{
    protected $table         = 'interventi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'codice', 'cliente_id', 'tecnico_id',
        'priorita', 'stato', 'tipo_intervento_id',
        'data_pianificata', 'data_scadenza', 'durata_stimata', 'urgenza',
        'impianto_id', 'note',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: programmato, normale, urgente
    const PRIORITA_PROGRAMMATO = 'programmato';
    const PRIORITA_NORMALE     = 'normale';
    const PRIORITA_URGENTE     = 'urgente';

    const PRIORITA_LABEL = [
        'programmato' => 'Programmato',
        'normale'     => 'Normale',
        'urgente'     => 'Urgente',
    ];

    // valori: da_pianificare, pianificato, in_corso, completato, annullato
    const STATO_DA_PIANIFICARE = 'da_pianificare';
    const STATO_PIANIFICATO    = 'pianificato';
    const STATO_IN_CORSO       = 'in_corso';
    const STATO_COMPLETATO     = 'completato';
    const STATO_ANNULLATO      = 'annullato';

    const STATI_LABEL = [
        'da_pianificare' => 'Da pianificare',
        'pianificato'    => 'Pianificato',
        'in_corso'       => 'In corso',
        'completato'     => 'Completato',
        'annullato'      => 'Annullato',
    ];

    /**
     * Imposta created_by/updated_by, nullifica i campi opzionali vuoti e normalizza urgenza.
     */
    protected function normalizza(array $data): array
    {
        $userId = session()->get('user_id');

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
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
     * Genera il prossimo codice IV-xxxx per un nuovo intervento.
     */
    public function generaCodice(): string
    {
        $row = $this->select('codice')
            ->like('codice', 'IV-', 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if (! $row) {
            return 'IV-0001';
        }

        $numero = (int) substr($row['codice'], 3);
        return 'IV-' . str_pad($numero + 1, 4, '0', STR_PAD_LEFT);
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
}
