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
     * Promemoria da oggi in avanti, entro una finestra di giorni. Solo confronto
     * sulla data (nessun filtro sull'orario): un promemoria di oggi resta incluso
     * anche a orario già passato. Include il flag `letto` (dismiss dell'utente
     * indicato): i promemoria già letti non vengono esclusi, solo segnalati.
     */
    public function inArrivo(int $utenteId, int $giorni = 14): array
    {
        $oggi = date('Y-m-d');
        $fine = date('Y-m-d', strtotime("+{$giorni} days"));

        return $this->select('promemoria.*, (pd.id IS NOT NULL) AS letto')
            ->join('promemoria_dismiss pd', 'pd.promemoria_id = promemoria.id AND pd.utente_id = ' . $this->db->escape($utenteId), 'left')
            ->where('DATE(data_ora_inizio) >=', $oggi)
            ->where('DATE(data_ora_inizio) <=', $fine)
            ->orderBy('data_ora_inizio', 'ASC')
            ->findAll();
    }

    /**
     * Promemoria in arrivo divisi in due fasce: quelli di oggi ("oggi", il badge
     * della campanella conta solo questi) e quelli dei prossimi giorni entro la
     * finestra ("prossimi"). Riusato dalla campanella e dalla dashboard.
     *
     * @return array{oggi: array, prossimi: array}
     */
    public function inArrivoRaggruppati(int $utenteId, int $giorni = 14): array
    {
        $oggi   = date('Y-m-d');
        $gruppi = ['oggi' => [], 'prossimi' => []];

        foreach ($this->inArrivo($utenteId, $giorni) as $p) {
            $chiave = substr($p['data_ora_inizio'], 0, 10) === $oggi ? 'oggi' : 'prossimi';
            $gruppi[$chiave][] = $p;
        }

        return $gruppi;
    }

    /**
     * Promemoria di oggi (data_ora_inizio) che l'utente indicato non ha ancora
     * chiuso. Usato per la modal informativa mostrata ad ogni accesso.
     */
    public function oggiNonVisti(int $utenteId): array
    {
        return $this->select('promemoria.*')
            ->join('promemoria_dismiss pd', 'pd.promemoria_id = promemoria.id AND pd.utente_id = ' . $this->db->escape($utenteId), 'left')
            ->where('DATE(data_ora_inizio)', date('Y-m-d'))
            ->where('pd.id', null)
            ->orderBy('data_ora_inizio', 'ASC')
            ->findAll();
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

        // Fine non specificata: default a inizio + 1 ora (come Google Calendar),
        // invece di NULL — evita che il promemoria risulti "scaduto" (e sparisca
        // dalla campanella) nell'istante esatto dell'orario di inizio.
        if (empty($data['data']['data_ora_fine'])) {
            $data['data']['data_ora_fine'] = ! empty($data['data']['data_ora_inizio'])
                ? date('Y-m-d H:i:s', strtotime($data['data']['data_ora_inizio']) + 3600)
                : null;
        }

        if (isset($data['data']['note']) && $data['data']['note'] === '') {
            $data['data']['note'] = null;
        }

        return $data;
    }
}
