<?php

namespace App\Models;

use CodeIgniter\Model;

class PromemoriaDismissModel extends Model
{
    protected $table         = 'promemoria_dismiss';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['promemoria_id', 'utente_id', 'created_at'];

    /**
     * Segna il promemoria come visto dall'utente. Usa INSERT IGNORE: se l'utente
     * ha già chiuso lo stesso promemoria (chiave unica promemoria_id+utente_id)
     * il doppio invio non genera errore.
     */
    public function segnaVisto(int $promemoriaId, int $utenteId): void
    {
        $this->builder()->ignore(true)->insert([
            'promemoria_id' => $promemoriaId,
            'utente_id'     => $utenteId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
