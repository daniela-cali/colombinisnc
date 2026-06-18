<?php

namespace App\Models;

use CodeIgniter\Model;

class InterventiMaterialiModel extends Model
{
    protected $table         = 'interventi_materiali';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'intervento_id', 'articolo_id', 'descrizione', 'quantita', 'note', 'stato',
        'created_by', 'updated_by',
    ];

    // valori: da_portare, consegnato
    const STATO_DA_PORTARE = 'da_portare';
    const STATO_CONSEGNATO = 'consegnato';

    const STATI_LABEL = [
        'da_portare' => 'Da portare',
        'consegnato' => 'Consegnato',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by/updated_by e nullifica note vuote.
     */
    protected function normalizza(array $data): array
    {
        $userId = session()->get('user_id');

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
            if (empty($data['data']['stato'])) {
                $data['data']['stato'] = self::STATO_DA_PORTARE;
            }
        }
        $data['data']['updated_by'] = $userId;

        foreach (['articolo_id', 'note'] as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }

    /**
     * Restituisce tutti i materiali di un intervento con descrizione da catalogo (fallback su testo libero).
     */
    public function perIntervento(int $interventoId): array
    {
        return $this->select([
                'interventi_materiali.*',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
            ])
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->where('interventi_materiali.intervento_id', $interventoId)
            ->findAll();
    }

    /**
     * Restituisce tutti i materiali consegnati negli interventi di un cliente,
     * con descrizione articolo da catalogo (fallback su testo libero) e dati intervento.
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select([
                'interventi_materiali.id',
                'interventi_materiali.quantita',
                'interventi_materiali.note',
                'interventi_materiali.stato',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
                'i.id AS intervento_id_ref',
                'i.codice AS codice_intervento',
                'i.data_pianificata',
            ])
            ->join('interventi i', 'i.id = interventi_materiali.intervento_id')
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->orderBy('i.data_pianificata', 'DESC')
            ->orderBy('interventi_materiali.id', 'ASC')
            ->findAll();
    }
}
