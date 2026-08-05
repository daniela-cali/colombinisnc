<?php

namespace App\Models;

use CodeIgniter\Model;

class CantieriModel extends Model
{
    protected $table         = 'cantieri';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'cliente_id', 'titolo', 'tipo', 'tipo_intervento_id', 'stato',
        'indirizzo', 'citta', 'referente_nome', 'referente_telefono',
        'lat', 'lng', 'geocoded_at', 'geocodifica_fallita',
        'data_inizio', 'data_fine_prevista', 'note',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: nuova_costruzione, ristrutturazione, manutenzione_straordinaria
    const TIPO_NUOVA_COSTRUZIONE           = 'nuova_costruzione';
    const TIPO_RISTRUTTURAZIONE            = 'ristrutturazione';
    const TIPO_MANUTENZIONE_STRAORDINARIA  = 'manutenzione_straordinaria';

    const TIPI_LABEL = [
        'nuova_costruzione'          => 'Nuova costruzione',
        'ristrutturazione'           => 'Ristrutturazione',
        'manutenzione_straordinaria' => 'Manutenzione straordinaria',
    ];

    // valori: aperto, sospeso, chiuso
    const STATO_APERTO  = 'aperto';
    const STATO_SOSPESO = 'sospeso';
    const STATO_CHIUSO  = 'chiuso';

    const STATI_LABEL = [
        'aperto'  => 'Aperto',
        'sospeso' => 'Sospeso',
        'chiuso'  => 'Chiuso',
    ];

    const STATI_BADGE = [
        'aperto'  => 'bg-success',
        'sospeso' => 'bg-warning text-dark',
        'chiuso'  => 'bg-secondary',
    ];

    /**
     * Imposta created_by all'inserimento, updated_by ad ogni salvataggio,
     * e nullifica le date opzionali arrivate vuote dal form.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! array_key_exists('id', $data)) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['citta']) && $data['data']['citta'] !== '') {
            $data['data']['citta'] = mb_strtoupper($data['data']['citta'], 'UTF-8');
        }

        foreach (['data_inizio', 'data_fine_prevista', 'tipo_intervento_id', 'lat', 'lng', 'geocoded_at'] as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }

    /**
     * Cantieri di un cliente per la sezione "indice sottile" della scheda cliente:
     * ogni riga porta il contatore interventi da pianificare e l'anteprima dell'ultima nota,
     * così la view mostra "⚠ N da pianificare" e il polso del cantiere senza query extra.
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select("cantieri.*,
                (SELECT COUNT(*) FROM interventi i
                 WHERE i.cantiere_id = cantieri.id AND i.stato = 'da_pianificare') AS num_da_pianificare,
                (SELECT cn.testo FROM cantieri_note cn
                 WHERE cn.cantiere_id = cantieri.id
                 ORDER BY cn.data_nota DESC, cn.id DESC LIMIT 1) AS ultima_nota_testo,
                (SELECT cn.data_nota FROM cantieri_note cn
                 WHERE cn.cantiere_id = cantieri.id
                 ORDER BY cn.data_nota DESC, cn.id DESC LIMIT 1) AS ultima_nota_data")
            ->where('cantieri.cliente_id', $clienteId)
            ->orderBy('cantieri.stato', 'ASC')
            ->orderBy('cantieri.data_inizio', 'DESC')
            ->findAll();
    }

    /**
     * Singolo cantiere con la denominazione del cliente, per l'header della scheda cantiere.
     */
    public function conCliente(int $id): ?array
    {
        return $this->select("cantieri.*,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione,
                c.indirizzo AS cliente_indirizzo, c.citta AS cliente_citta,
                c.lat AS cliente_lat, c.lng AS cliente_lng, c.nazione AS cliente_nazione")
            ->join('clienti c', 'c.id = cantieri.cliente_id', 'left')
            ->where('cantieri.id', $id)
            ->first();
    }

    /**
     * Lista globale con denominazione cliente, per i filtri stato/cliente.
     */
    public function elencoCompleto(): array
    {
        return $this->select("cantieri.*,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc
                END AS cliente_denominazione")
            ->join('clienti c', 'c.id = cantieri.cliente_id', 'left')
            ->orderBy('cantieri.stato', 'ASC')
            ->orderBy('cantieri.data_inizio', 'DESC')
            ->findAll();
    }

}
