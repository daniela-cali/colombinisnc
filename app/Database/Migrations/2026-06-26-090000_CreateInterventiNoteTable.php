<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInterventiNoteTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'intervento_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'data_nota'     => ['type' => 'DATE', 'null' => false],
            'testo'         => ['type' => 'TEXT', 'null' => false],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('intervento_id');

        $this->forge->addForeignKey('intervento_id', 'interventi', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('interventi_note');
    }

    public function down(): void
    {
        $this->forge->dropTable('interventi_note', true);
    }
}
