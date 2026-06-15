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
        'intervento_id', 'descrizione', 'quantita', 'note',
        'created_by', 'updated_by',
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
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['note']) && $data['data']['note'] === '') {
            $data['data']['note'] = null;
        }

        return $data;
    }

    /**
     * Restituisce tutti i materiali di un intervento.
     */
    public function perIntervento(int $interventoId): array
    {
        return $this->where('intervento_id', $interventoId)->findAll();
    }
}
