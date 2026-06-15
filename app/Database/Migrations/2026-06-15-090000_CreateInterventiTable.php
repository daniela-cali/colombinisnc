<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInterventiTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            // Codice visibile formato IV-0001
            'codice'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],

            'cliente_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'tecnico_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            // valori: programmato, normale, sopralluogo, commerciale
            'tipo'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'normale'],

            // valori: pianificato, confermato, in_corso, completato, annullato
            'stato'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pianificato'],

            'data_pianificata' => ['type' => 'DATETIME', 'null' => true],
            'data_scadenza'    => ['type' => 'DATE',     'null' => true],

            // Durata stimata in minuti; nullable — viene pre-compilata dal default in Parametri
            'durata_stimata'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            // Flag urgenza indipendente dal tipo: 1 = urgente
            'urgenza'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],

            // FK placeholder per impianti (v0.11.0) — non referenziata ora per evitare ALTER futuri
            'impianto_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            'note'            => ['type' => 'TEXT', 'null' => true],

            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codice');
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('tecnico_id');
        $this->forge->addKey('stato');
        $this->forge->addKey('data_pianificata');

        $this->forge->addForeignKey('cliente_id', 'clienti',   'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('tecnico_id', 'personale',  'id', 'RESTRICT', 'SET NULL');

        $this->forge->createTable('interventi');
    }

    public function down(): void
    {
        $this->forge->dropTable('interventi', true);
    }
}
