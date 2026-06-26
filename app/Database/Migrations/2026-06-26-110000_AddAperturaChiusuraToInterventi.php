<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAperturaChiusuraToInterventi extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('interventi', [
            'apertura' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'pulizia_fondo',
            ],
            'chiusura' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'apertura',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('interventi', ['apertura', 'chiusura']);
    }
}
