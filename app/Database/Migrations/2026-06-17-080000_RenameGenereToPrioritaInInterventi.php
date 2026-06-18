<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameGenereToPrioritaInInterventi extends Migration
{
    public function up(): void
    {
        // Mappa i vecchi valori ai nuovi prima di rinominare la colonna
        $this->db->query("UPDATE interventi SET genere = 'normale' WHERE genere IN ('sopralluogo', 'commerciale')");

        // Rinomina genere → priorita e aggiorna il commento sui valori ammessi
        $this->forge->modifyColumn('interventi', [
            'genere' => [
                'name'       => 'priorita',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'normale',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('interventi', [
            'priorita' => [
                'name'       => 'genere',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'normale',
            ],
        ]);
    }
}
