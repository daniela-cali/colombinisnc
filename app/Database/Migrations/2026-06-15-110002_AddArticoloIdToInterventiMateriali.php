<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddArticoloIdToInterventiMateriali extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('interventi_materiali', [
            'articolo_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'intervento_id',
            ],
        ]);
        $this->forge->addForeignKey('articolo_id', 'articoli', 'id', 'SET NULL', 'SET NULL');
        $this->forge->processIndexes('interventi_materiali');
    }

    public function down(): void
    {
        // La chiave va rimossa prima della colonna: l'indice che la accompagna serve al
        // vincolo, e MySQL rifiuta di eliminarlo finché il vincolo esiste.
        $this->forge->dropForeignKey('interventi_materiali', 'interventi_materiali_articolo_id_foreign');
        $this->forge->dropColumn('interventi_materiali', 'articolo_id');
    }
}
