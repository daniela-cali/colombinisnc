<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClienteIdToInterventiMateriali extends Migration
{
    public function up(): void
    {
        // Aggiunge cliente_id nullable per permettere il populate dei record esistenti
        $this->forge->addColumn('interventi_materiali', [
            'cliente_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
        ]);

        // Popola cliente_id dai record esistenti tramite JOIN con interventi
        $this->db->query('
            UPDATE interventi_materiali im
            JOIN interventi i ON i.id = im.intervento_id
            SET im.cliente_id = i.cliente_id
        ');

        // Rende cliente_id NOT NULL e aggiunge FK
        $this->forge->modifyColumn('interventi_materiali', [
            'cliente_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);

        $this->forge->addForeignKey('cliente_id', 'clienti', 'id', '', 'CASCADE');
        $this->forge->processIndexes('interventi_materiali');

        // Rende intervento_id nullable (materiali sospesi senza intervento)
        $this->forge->modifyColumn('interventi_materiali', [
            'intervento_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('interventi_materiali', 'interventi_materiali_cliente_id_foreign');
        $this->forge->dropColumn('interventi_materiali', 'cliente_id');

        $this->forge->modifyColumn('interventi_materiali', [
            'intervento_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);
    }
}
