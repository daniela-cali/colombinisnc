<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateInterventiGenere extends Migration
{
    public function up(): void
    {
        // Rinomina tipo → genere (stessa definizione, nome più chiaro rispetto a tipo_intervento_id)
        $this->forge->modifyColumn('interventi', [
            'tipo' => [
                'name'       => 'genere',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'normale',
            ],
        ]);

        // Aggiunge FK al tipo di lavoro (piscine, filtri, ecc.)
        $this->forge->addColumn('interventi', [
            'tipo_intervento_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'genere',
            ],
        ]);

        $this->forge->addForeignKey('tipo_intervento_id', 'tipi_intervento', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->processIndexes('interventi');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('interventi', 'interventi_tipo_intervento_id_foreign');
        $this->forge->dropColumn('interventi', 'tipo_intervento_id');

        $this->forge->modifyColumn('interventi', [
            'genere' => [
                'name'       => 'tipo',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'normale',
            ],
        ]);
    }
}
