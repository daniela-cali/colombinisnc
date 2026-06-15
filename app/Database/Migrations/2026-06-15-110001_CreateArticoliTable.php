<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticoliTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'codice'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'descrizione'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'categoria_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            // valori: pz, kg, lt, m2, m, conf
            'unita_misura' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pz'],
            'costo'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'vendita'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'giacenza'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0],
            'attivo'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');
        $this->forge->addKey('id', true);
        $this->forge->addKey('categoria_id');
        $this->forge->addForeignKey('categoria_id', 'categorie_articoli', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('articoli');
    }

    public function down(): void
    {
        $this->forge->dropTable('articoli');
    }
}
