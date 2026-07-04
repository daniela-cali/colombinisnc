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
        'codice', 'nome', 'categoria', 'icona', 'durata_default', 'attivo', 'abbonabile', 'ordine',
        'prefisso_codice', 'ha_pulizia_fondo',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: generale, piscine, addolcitori — definiscono la sezione operativa degli interventi.
    // 'generale' è la sezione catch-all "Interventi". L'etichetta è anche il nome della voce di menu.
    const CATEGORIA_GENERALE    = 'generale';
    const CATEGORIA_PISCINE     = 'piscine';
    const CATEGORIA_ADDOLCITORI = 'addolcitori';

    const CATEGORIE_LABEL = [
        'generale'    => 'Generici',
        'piscine'     => 'Piscine',
        'addolcitori' => 'Addolcitori',
    ];

    /**
     * Imposta created_by/updated_by.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['icona']) && $data['data']['icona'] === '') {
            $data['data']['icona'] = null;
        }

        if (isset($data['data']['prefisso_codice'])) {
            $p = strtoupper(trim($data['data']['prefisso_codice']));
            $data['data']['prefisso_codice'] = $p !== '' ? substr($p, 0, 3) : null;
        }

        // Categoria mai vuota: ricade nella sezione generica se non specificata.
        if (array_key_exists('categoria', $data['data']) && empty($data['data']['categoria'])) {
            $data['data']['categoria'] = self::CATEGORIA_GENERALE;
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
     * Restituisce i tipi attivi e marcati come abbonabili — usato per le select negli abbonamenti.
     */
    public function abbonabili(): array
    {
        return $this->where('attivo', 1)->where('abbonabile', 1)->orderBy('ordine')->findAll();
    }

    /**
     * Restituisce tutti i tipi (anche inattivi) ordinati per `ordine`.
     */
    public function tutti(): array
    {
        return $this->orderBy('ordine')->findAll();
    }
}
