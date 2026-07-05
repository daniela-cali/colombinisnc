<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssenzeTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'personale_id' => ['type' => 'INT', 'unsigned' => true],
        ]);

        // valori: ferie, malattia, permesso, altro — vedi costanti in AssenzeModel
        $this->forge->addField("tipo VARCHAR(20) NOT NULL COMMENT 'ferie, malattia, permesso, altro'");
        $this->forge->addField('data_inizio DATE NOT NULL');
        $this->forge->addField('data_fine DATE NOT NULL');

        $this->forge->addField([
            'note'       => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('personale_id');
        $this->forge->addKey('data_inizio');
        $this->forge->addForeignKey('personale_id', 'personale', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('assenze');
    }

    public function down(): void
    {
        $this->forge->dropTable('assenze', true);
    }
}
