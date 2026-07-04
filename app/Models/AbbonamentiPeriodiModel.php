<?php

namespace App\Models;

use CodeIgniter\Model;

class AbbonamentiPeriodiModel extends Model
{
    protected $table         = 'abbonamenti_periodi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'abbonamento_id', 'data_inizio', 'data_fine', 'frequenza',
        'con_pulizia_fondo', 'ordine',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by/updated_by.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        return $data;
    }

    /**
     * Restituisce i periodi di un abbonamento ordinati per ordine ASC.
     */
    public function perAbbonamento(int $abbonamentoId): array
    {
        return $this->where('abbonamento_id', $abbonamentoId)
                    ->orderBy('ordine', 'ASC')
                    ->findAll();
    }
}
