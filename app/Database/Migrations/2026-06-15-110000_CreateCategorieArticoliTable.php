<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCategorieArticoliTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nome'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'ordine'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');
        $this->forge->addKey('id', true);
        $this->forge->createTable('categorie_articoli');
    }

    public function down(): void
    {
        $this->forge->dropTable('categorie_articoli');
    }
}
