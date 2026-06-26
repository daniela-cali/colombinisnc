<?php

namespace App\Models;

use CodeIgniter\Model;

class InterventiNoteModel extends Model
{
    protected $table         = 'interventi_note';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'intervento_id', 'data_nota', 'testo',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by all'inserimento e updated_by ad ogni salvataggio.
     * Se data_nota non è valorizzata, usa la data odierna: così una nota scritta
     * "al volo" non obbliga a digitare la data.
     */
    protected function normalizza(array $data): array
    {
        $userId = session()->get('user_id');

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
            if (empty($data['data']['data_nota'])) {
                $data['data']['data_nota'] = date('Y-m-d');
            }
        }
        $data['data']['updated_by'] = $userId;

        return $data;
    }

    /**
     * Diario di un intervento: note dalla più recente, con l'autore (nome cognome)
     * ricavato dal personale collegato all'utente che ha creato la nota.
     */
    public function perIntervento(int $interventoId): array
    {
        return $this->select([
                'interventi_note.*',
                "TRIM(CONCAT(COALESCE(p.nome, ''), ' ', COALESCE(p.cognome, ''))) AS autore",
            ])
            ->join('personale p', 'p.user_id = interventi_note.created_by', 'left')
            ->where('interventi_note.intervento_id', $interventoId)
            ->orderBy('interventi_note.data_nota', 'DESC')
            ->orderBy('interventi_note.id', 'DESC')
            ->findAll();
    }
}
