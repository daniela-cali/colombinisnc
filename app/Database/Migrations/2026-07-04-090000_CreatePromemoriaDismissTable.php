<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromemoriaDismissTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'promemoria_id' => ['type' => 'INT', 'unsigned' => true],
            'utente_id'     => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['promemoria_id', 'utente_id']);

        $this->forge->addForeignKey('promemoria_id', 'promemoria', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('utente_id', 'users', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('promemoria_dismiss');
    }

    public function down(): void
    {
        $this->forge->dropTable('promemoria_dismiss', true);
    }
}
