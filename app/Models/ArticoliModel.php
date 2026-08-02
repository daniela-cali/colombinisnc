<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticoliModel extends Model
{
    protected $table         = 'articoli';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'codice', 'descrizione', 'categoria_id', 'unita_misura',
        'costo', 'vendita', 'giacenza', 'attivo',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    const UNITA_MISURA = [
        'pz'   => 'Pezzo',
        'kg'   => 'Kg',
        'lt'   => 'Litri',
        'conf' => 'Confezione',
        'sacco' => 'Sacco',
        'm'    => 'Metro',
        'm2'   => 'm²',
    ];

    /**
     * Imposta created_by/updated_by e nullifica i campi opzionali vuoti.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();
        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        foreach (['descrizione', 'codice'] as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] !== null) {
                $data['data'][$campo] = mb_strtoupper($data['data'][$campo]);
            }
        }

        $nullabili = ['categoria_id', 'costo', 'vendita', 'giacenza'];
        foreach ($nullabili as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        return $data;
    }

    /**
     * Lista completa con nome categoria, solo articoli attivi.
     */
    public function elencoAttivi(): array
    {
        return $this->select('articoli.*, c.nome AS categoria_nome')
            ->join('categorie_articoli c', 'c.id = articoli.categoria_id', 'left')
            ->where('articoli.attivo', 1)
            ->orderBy('c.ordine')
            ->orderBy('articoli.descrizione')
            ->findAll();
    }

    /**
     * Lista completa (attivi e non) con nome categoria.
     */
    public function elencoCompleto(): array
    {
        return $this->select('articoli.*, c.nome AS categoria_nome')
            ->join('categorie_articoli c', 'c.id = articoli.categoria_id', 'left')
            ->orderBy('c.ordine')
            ->orderBy('articoli.descrizione')
            ->findAll();
    }

    /**
     * Articoli attivi raggruppati per categoria_id — utile per select optgroup.
     * Restituisce [categoria_id => ['nome' => ..., 'articoli' => [...]]]
     */
    public function perCategoria(): array
    {
        $rows = $this->elencoAttivi();
        $out  = [];
        foreach ($rows as $r) {
            $cid = $r['categoria_id'] ?? 0;
            if (! isset($out[$cid])) {
                $out[$cid] = ['nome' => $r['categoria_nome'] ?? 'Senza categoria', 'articoli' => []];
            }
            $out[$cid]['articoli'][] = $r;
        }
        return $out;
    }

}
