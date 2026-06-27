<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCantieriTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        ]);

        $this->forge->addField("cliente_id INT UNSIGNED NOT NULL COMMENT 'FK → clienti.id'");
        $this->forge->addField("titolo VARCHAR(150) NOT NULL COMMENT 'nome breve del cantiere'");
        $this->forge->addField("tipo VARCHAR(30) NOT NULL DEFAULT 'nuova_costruzione' COMMENT 'nuova_costruzione | ristrutturazione'");
        $this->forge->addField("stato VARCHAR(20) NOT NULL DEFAULT 'aperto' COMMENT 'aperto | sospeso | chiuso'");
        $this->forge->addField('data_inizio DATE NULL');
        $this->forge->addField('data_fine_prevista DATE NULL');

        $this->forge->addField([
            'note'       => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('stato');

        $this->forge->addForeignKey('cliente_id', 'clienti', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('cantieri');
    }

    public function down(): void
    {
        $this->forge->dropTable('cantieri', true);
    }
}
