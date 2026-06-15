<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTipiInterventoTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'codice'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'nome'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'icona'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'durata_default' => ['type' => 'INT', 'unsigned' => true, 'default' => 60],
            'attivo'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'ordine'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codice');
        $this->forge->addKey('ordine');

        $this->forge->createTable('tipi_intervento');
    }

    public function down(): void
    {
        $this->forge->dropTable('tipi_intervento', true);
    }
}
