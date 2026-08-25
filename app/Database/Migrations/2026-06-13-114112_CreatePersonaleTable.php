<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersonaleTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nome'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'cognome'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'telefono'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            // colore hex (#RRGGBB) del profilo — usato nel calendario e ovunque serva identificare la persona
            'colore'             => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
            'attivo'             => ['type' => 'TINYINT', 'default' => 1],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('personale');
    }

    public function down(): void
    {
        $this->forge->dropTable('personale', true);
    }
}
