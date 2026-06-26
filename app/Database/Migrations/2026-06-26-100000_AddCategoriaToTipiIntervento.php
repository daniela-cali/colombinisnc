<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCategoriaToTipiIntervento extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tipi_intervento', [
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'generale',
                'after'      => 'nome',
            ],
        ]);

        // Stato iniziale sensato sui tipi seed: piscine e addolcitori hanno una sezione propria,
        // tutti gli altri restano nella sezione generica. Modificabile poi dall'utente nei Tipi intervento.
        $this->db->table('tipi_intervento')->where('codice', 'piscine')->update(['categoria' => 'piscine']);
        $this->db->table('tipi_intervento')->where('codice', 'addolcitori')->update(['categoria' => 'addolcitori']);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tipi_intervento', 'categoria');
    }
}
