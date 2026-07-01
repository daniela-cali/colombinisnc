<?php

namespace App\Models;

use CodeIgniter\Model;

class PromemoriaModel extends Model
{
    protected $table         = 'promemoria';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'titolo', 'data_ora_inizio', 'data_ora_fine', 'note',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Promemoria ancora in corso o futuri, entro una finestra di giorni in avanti.
     * Usato dalla campanella e dalla dashboard per mostrare i "prossimi in arrivo".
     */
    public function inArrivo(int $giorni = 14): array
    {
        $ora   = date('Y-m-d H:i:s');
        $fine  = date('Y-m-d 23:59:59', strtotime("+{$giorni} days"));

        return $this->where('COALESCE(data_ora_fine, data_ora_inizio) >=', $ora)
            ->where('data_ora_inizio <=', $fine)
            ->orderBy('data_ora_inizio', 'ASC')
            ->findAll();
    }

    /**
     * Promemoria in arrivo divisi in due fasce: quelli entro la fine di questa
     * settimana ("settimana", gli imminenti) e quelli successivi entro la
     * finestra ("prossimi"). Riusato dalla campanella e dalla dashboard.
     *
     * @return array{settimana: array, prossimi: array}
     */
    public function inArrivoRaggruppati(int $giorni = 14): array
    {
        $fineSettimana = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $gruppi        = ['settimana' => [], 'prossimi' => []];

        foreach ($this->inArrivo($giorni) as $p) {
            $chiave = $p['data_ora_inizio'] <= $fineSettimana ? 'settimana' : 'prossimi';
            $gruppi[$chiave][] = $p;
        }

        return $gruppi;
    }

    /**
     * Promemoria che intersecano l'intervallo [start, end] del calendario.
     * Un promemoria rientra se inizia prima della fine del range e finisce
     * (o, se puntuale, inizia) dopo l'inizio del range.
     */
    public function perCalendario(string $start, string $end): array
    {
        return $this->where('data_ora_inizio <', $end)
            ->where('COALESCE(data_ora_fine, data_ora_inizio) >=', $start)
            ->orderBy('data_ora_inizio', 'ASC')
            ->findAll();
    }

    /**
     * Imposta created_by/updated_by, converte i datetime-local per MySQL
     * e nullifica i campi opzionali vuoti.
     */
    protected function normalizza(array $data): array
    {
        $userId = session()->get('user_id');

        if (! array_key_exists('id', $data)) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        // datetime-local invia 'YYYY-MM-DDTHH:MM' — converte a formato MySQL
        foreach (['data_ora_inizio', 'data_ora_fine'] as $campo) {
            if (! empty($data['data'][$campo]) && str_contains($data['data'][$campo], 'T')) {
                $data['data'][$campo] = date('Y-m-d H:i:s', strtotime($data['data'][$campo]));
            }
        }

        foreach (['data_ora_fine', 'note'] as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }
}
