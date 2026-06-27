<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCantieriNoteTable extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // Tabella cantieri_note — diario cronologico del cantiere
        // (gemella di interventi_note)
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'cantiere_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'data_nota'   => ['type' => 'DATE', 'null' => false],
            'testo'       => ['type' => 'TEXT', 'null' => false],
            'created_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('cantiere_id');

        $this->forge->addForeignKey('cantiere_id', 'cantieri', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('cantieri_note');

        // ----------------------------------------------------------------
        // FK cantiere_id su interventi (gemella di abbonamento_id)
        // ----------------------------------------------------------------
        $this->forge->addColumn('interventi', [
            "cantiere_id INT UNSIGNED NULL COMMENT 'FK → cantieri.id — NULL = non legato a cantiere' AFTER abbonamento_id",
        ]);
        $this->db->query('ALTER TABLE interventi ADD CONSTRAINT fk_interventi_cantiere FOREIGN KEY (cantiere_id) REFERENCES cantieri(id) ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE interventi DROP FOREIGN KEY fk_interventi_cantiere');
        $this->forge->dropColumn('interventi', 'cantiere_id');

        $this->forge->dropTable('cantieri_note', true);
    }
}
