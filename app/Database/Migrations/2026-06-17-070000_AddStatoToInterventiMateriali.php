<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatoToInterventiMateriali extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('interventi_materiali', [
            // valori: da_portare, consegnato
            'stato' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'da_portare',
                'after'      => 'note',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('interventi_materiali', 'stato');
    }
}
