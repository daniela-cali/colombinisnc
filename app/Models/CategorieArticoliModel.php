<?php

namespace App\Models;

use CodeIgniter\Model;

class CategorieArticoliModel extends Model
{
    protected $table         = 'categorie_articoli';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['nome', 'ordine', 'created_by', 'updated_by'];

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

        return $data;
    }

    /**
     * Tutte le categorie ordinate per ordine, poi per nome.
     */
    public function tutteOrdinate(): array
    {
        return $this->orderBy('ordine')->orderBy('nome')->findAll();
    }
}
