<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperazioniStandardToTipiIntervento extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tipi_intervento', [
            'operazioni_standard' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'attivo',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tipi_intervento', 'operazioni_standard');
    }
}
