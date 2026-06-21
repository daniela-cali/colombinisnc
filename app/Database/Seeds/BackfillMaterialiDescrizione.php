<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Backfill una-tantum: copia articoli.descrizione in interventi_materiali.descrizione
 * per tutti i record collegati a un articolo che non hanno ancora la descrizione.
 *
 * Eseguire con: php spark db:seed BackfillMaterialiDescrizione
 */
class BackfillMaterialiDescrizione extends Seeder
{
    public function run(): void
    {
        $this->db->query("
            UPDATE interventi_materiali im
            JOIN articoli a ON a.id = im.articolo_id
            SET im.descrizione = a.descrizione
            WHERE im.articolo_id IS NOT NULL
              AND (im.descrizione IS NULL OR im.descrizione = '')
        ");

        $count = $this->db->affectedRows();
        CLI::write("Backfill completato: {$count} record aggiornati.", 'green');
    }
}
