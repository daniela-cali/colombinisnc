<?php

namespace App\Models;

use CodeIgniter\Model;

class TipiInterventoModel extends Model
{
    protected $table         = 'tipi_intervento';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'codice', 'nome', 'icona', 'durata_default', 'attivo', 'ordine',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by/updated_by.
     */
    protected function normalizza(array $data): array
    {
        $userId = session()->get('user_id');

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['icona']) && $data['data']['icona'] === '') {
            $data['data']['icona'] = null;
        }

        return $data;
    }

    /**
     * Restituisce i tipi attivi ordinati per `ordine`.
     */
    public function attivi(): array
    {
        return $this->where('attivo', 1)->orderBy('ordine')->findAll();
    }

    /**
     * Restituisce tutti i tipi (anche inattivi) ordinati per `ordine`.
     */
    public function tutti(): array
    {
        return $this->orderBy('ordine')->findAll();
    }
}
