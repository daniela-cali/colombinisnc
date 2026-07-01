<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromemoriaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        ]);

        $this->forge->addField("titolo VARCHAR(150) NOT NULL COMMENT 'testo breve del promemoria'");
        $this->forge->addField('data_ora_inizio DATETIME NOT NULL');
        $this->forge->addField('data_ora_fine DATETIME NULL');

        $this->forge->addField([
            'note'       => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('data_ora_inizio');

        $this->forge->createTable('promemoria');
    }

    public function down(): void
    {
        $this->forge->dropTable('promemoria', true);
    }
}
