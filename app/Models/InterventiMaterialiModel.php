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
        'cliente_id', 'intervento_id', 'articolo_id', 'descrizione', 'quantita', 'note', 'stato',
        'created_by', 'updated_by',
    ];

    // valori: da_portare, consegnato
    const STATO_DA_PORTARE = 'da_portare';
    const STATO_CONSEGNATO = 'consegnato';

    const STATI_LABEL = [
        'da_portare' => 'Da portare',
        'consegnato' => 'Consegnato',
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
            if (empty($data['data']['stato'])) {
                $data['data']['stato'] = self::STATO_DA_PORTARE;
            }
        }
        $data['data']['updated_by'] = $userId;

        // Copia la descrizione dall'articolo se il campo è vuoto
        if (! empty($data['data']['articolo_id']) && empty($data['data']['descrizione'])) {
            $articolo = (new \App\Models\ArticoliModel())
                ->select('descrizione')
                ->where('id', (int) $data['data']['articolo_id'])
                ->get()->getRowArray();
            if ($articolo) {
                $data['data']['descrizione'] = $articolo['descrizione'];
            }
        }

        foreach (['intervento_id', 'articolo_id', 'note'] as $campo) {
            if (array_key_exists($campo, $data['data']) && empty($data['data'][$campo])) {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }

    /**
     * Restituisce tutti i materiali di un intervento con descrizione da catalogo (fallback su testo libero).
     */
    public function perIntervento(int $interventoId): array
    {
        return $this->select([
                'interventi_materiali.*',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
            ])
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->where('interventi_materiali.intervento_id', $interventoId)
            ->findAll();
    }

    /**
     * Materiali con intervento associato per un cliente (usati nel rowGroup della scheda cliente).
     */
    public function perCliente(int $clienteId): array
    {
        return $this->select([
                'interventi_materiali.id',
                'interventi_materiali.quantita',
                'interventi_materiali.note',
                'interventi_materiali.stato',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
                'i.id AS intervento_id_ref',
                'i.codice AS codice_intervento',
                'i.data_pianificata',
                'i.stato AS stato_intervento',
            ])
            ->join('interventi i', 'i.id = interventi_materiali.intervento_id')
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->where('interventi_materiali.cliente_id', $clienteId)
            ->where('interventi_materiali.intervento_id IS NOT NULL', null, false)
            ->orderBy('i.data_pianificata', 'DESC')
            ->orderBy('interventi_materiali.id', 'ASC')
            ->findAll();
    }

    /**
     * Segna come consegnati tutti i materiali di un intervento.
     */
    public function consegnaPerIntervento(int $interventoId): void
    {
        $this->where('intervento_id', $interventoId)
             ->set('stato', self::STATO_CONSEGNATO)
             ->update();
    }

    /**
     * Riporta tra i sospesi i materiali non consegnati di un intervento.
     * Prepende "[Da {codice}]" alla nota per conservare la traccia dell'origine.
     */
    public function liberaPerIntervento(int $interventoId, string $codice): void
    {
        $materiali = $this->where('intervento_id', $interventoId)
                          ->where('stato', self::STATO_DA_PORTARE)
                          ->findAll();

        foreach ($materiali as $m) {
            $nota = trim('[Da ' . $codice . '] ' . ($m['note'] ?? ''));
            $this->update($m['id'], [
                'intervento_id' => null,
                'note'          => $nota ?: null,
            ]);
        }
    }

    /**
     * Restituisce gli ID dei materiali da_portare di un intervento.
     * Va chiamato prima di liberaPerIntervento() — dopo, intervento_id è già null e non sapremmo più quali erano.
     */
    public function idsDaPortarePerIntervento(int $interventoId): array
    {
        return array_column(
            $this->select('id')
                 ->where('intervento_id', $interventoId)
                 ->where('stato', self::STATO_DA_PORTARE)
                 ->findAll(),
            'id'
        );
    }

    /**
     * Sposta i materiali indicati (per ID) su un altro intervento.
     * Usato dopo liberaPerIntervento() per riassegnare i sospesi al prossimo intervento dell'abbonamento.
     */
    public function assegnaAdIntervento(array $ids, int $interventoId): void
    {
        if (empty($ids)) {
            return;
        }
        $this->whereIn('id', $ids)->set(['intervento_id' => $interventoId])->update();
    }

    /**
     * Materiali "da portare" di più interventi in una sola query.
     * Restituisce descrizione grezza e note (foglio viaggio) più desc_materiale
     * dal catalogo con fallback sul testo libero (agenda mobile del tecnico).
     */
    public function daPortarePerInterventi(array $interventiIds): array
    {
        if (empty($interventiIds)) {
            return [];
        }

        return $this->select([
                'interventi_materiali.intervento_id',
                'interventi_materiali.quantita',
                'interventi_materiali.descrizione',
                'interventi_materiali.note',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
            ])
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->whereIn('interventi_materiali.intervento_id', $interventiIds)
            ->where('interventi_materiali.stato', self::STATO_DA_PORTARE)
            ->findAll();
    }

    /**
     * Numero di righe materiale che usano un articolo (blocco cancellazione articolo).
     */
    public function contaPerArticolo(int $articoloId): int
    {
        return $this->where('articolo_id', $articoloId)->countAllResults();
    }

    /**
     * Materiali sospesi di un cliente: da portare ma non ancora legati a un intervento.
     */
    public function sospesiPerCliente(int $clienteId): array
    {
        return $this->select([
                'interventi_materiali.*',
                'COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale',
            ])
            ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
            ->where('interventi_materiali.cliente_id', $clienteId)
            ->where('interventi_materiali.intervento_id IS NULL', null, false)
            ->orderBy('interventi_materiali.id', 'ASC')
            ->findAll();
    }
}
