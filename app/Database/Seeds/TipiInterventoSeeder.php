<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipiInterventoSeeder extends Seeder
{
    public function run(): void
    {
        $tipi = [
            ['codice' => 'sale',        'nome' => 'Consegna Sale',         'icona' => 'bi-bag',       'durata_default' => $this->durata('sale',        60), 'ordine' => 1],
            ['codice' => 'filtri',      'nome' => 'Cambio Filtri',         'icona' => 'bi-funnel',    'durata_default' => $this->durata('filtri',      60), 'ordine' => 2],
            ['codice' => 'piscine',     'nome' => 'Piscine',               'icona' => 'bi-water',     'durata_default' => $this->durata('piscine',     90), 'ordine' => 3],
            ['codice' => 'addolcitori', 'nome' => 'Addolcitori',           'icona' => 'bi-droplet',   'durata_default' => $this->durata('addolcitori', 60), 'ordine' => 4],
            ['codice' => 'acquedotti',  'nome' => 'Acquedotti',            'icona' => 'bi-pipe',      'durata_default' => $this->durata('acquedotti',  60), 'ordine' => 5],
            ['codice' => 'commerciale', 'nome' => 'Richiesta Commerciale', 'icona' => 'bi-briefcase', 'durata_default' => $this->durata('commerciale', 30), 'ordine' => 6],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($tipi as &$t) {
            $t['attivo']     = 1;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
        }

        $this->db->table('tipi_intervento')->insertBatch($tipi);
    }

    /** Legge la durata dal setting esistente, con fallback al valore di default. */
    private function durata(string $codice, int $default): int
    {
        $val = setting('Interventi.durata_' . $codice);
        return ($val !== null && (int) $val > 0) ? (int) $val : $default;
    }
}
