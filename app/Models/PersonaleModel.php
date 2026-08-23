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
     *
     * Esclude gli account disattivati: alimenta le tendine di assegnazione del tecnico
     * (calendario, nuovo intervento, viaggio, scheda cliente), e proporre chi non può più
     * entrare nel gestionale significherebbe assegnargli lavoro che non vedrà mai.
     * Chi va disattivato mantiene però i suoi interventi già assegnati, che restano
     * visibili ovunque: la riassegnazione è una scelta dell'operatore, non un effetto.
     *
     * $includiId riammette un singolo dipendente anche se sospeso, e va passato da ogni
     * form che modifica un record **già assegnato** a un tecnico (modifica intervento,
     * tecnico preferito del cliente). Senza, l'assegnato sospeso non è fra le opzioni,
     * il select ripiega su "nessuno" e il primo salvataggio azzera `tecnico_id` in
     * silenzio — anche su un intervento completato, cioè proprio sullo storico che la
     * sospensione esiste per proteggere. `status` è nel select perché la view possa
     * marcarlo come sospeso invece di presentarlo come una scelta qualsiasi.
     */
    public function elencoPerGruppi(array $gruppi, ?int $includiId = null): array
    {
        $builder = $this->select('personale.*, u.username, u.active, u.status, g.group as gruppo')
            ->join('users u', 'u.id = personale.user_id', 'inner')
            ->join('auth_groups_users g', 'g.user_id = personale.user_id', 'inner')
            ->whereIn('g.group', $gruppi);

        if ($includiId) {
            $builder->groupStart()
                ->where("COALESCE(u.status, '') !=", 'banned')
                ->orWhere('personale.id', $includiId)
                ->groupEnd();
        } else {
            $builder->where("COALESCE(u.status, '') !=", 'banned');
        }

        return $builder->orderBy('personale.cognome')
            ->orderBy('personale.nome')
            ->findAll();
    }
}
