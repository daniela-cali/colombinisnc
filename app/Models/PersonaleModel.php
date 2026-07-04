<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonaleModel extends Model
{
    protected $table         = 'personale';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'nome', 'cognome', 'telefono', 'colore', 'attivo',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by solo all'inserimento (non va mai sovrascritto negli update)
     * e updated_by ad ogni salvataggio.
     * CI4 passa $data['id'] solo negli update — assenza dell'id significa insert.
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
     * Restituisce il record personale collegato a un utente Shield.
     */
    public function perUtente(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Lista completa del personale con username e gruppo opzionali (LEFT JOIN).
     * Include anche chi non ha un account Shield.
     */
    public function elencoCompleto(): array
    {
        return $this->select('personale.*, u.username, GROUP_CONCAT(g.group ORDER BY g.group SEPARATOR ", ") as gruppi')
            ->join('users u', 'u.id = personale.user_id', 'left')
            ->join('auth_groups_users g', 'g.user_id = personale.user_id', 'left')
            ->groupBy('personale.id')
            ->orderBy('personale.cognome')
            ->orderBy('personale.nome')
            ->findAll();
    }

    /**
     * Colori già assegnati ad altri record personale.
     * Usato dal picker per marcare le scorciatoie già occupate.
     */
    public function coloriUsati(int $escludiId = 0): array
    {
        $builder = $this->select('colore')->where('colore IS NOT NULL', null, false);
        if ($escludiId > 0) {
            $builder = $builder->where('id !=', $escludiId);
        }
        return array_column($builder->findAll(), 'colore');
    }

    /**
     * Lista del personale con account app nei gruppi indicati,
     * ordinata per cognome e nome.
     */
    public function elencoPerGruppi(array $gruppi): array
    {
        return $this->select('personale.*, u.username, u.active, g.group as gruppo')
            ->join('users u', 'u.id = personale.user_id', 'inner')
            ->join('auth_groups_users g', 'g.user_id = personale.user_id', 'inner')
            ->whereIn('g.group', $gruppi)
            ->orderBy('personale.cognome')
            ->orderBy('personale.nome')
            ->findAll();
    }
}
