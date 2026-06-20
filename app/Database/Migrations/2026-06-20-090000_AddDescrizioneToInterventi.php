<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescrizioneToInterventi extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('interventi', [
            'descrizione' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'tecnico_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('interventi', 'descrizione');
    }
}