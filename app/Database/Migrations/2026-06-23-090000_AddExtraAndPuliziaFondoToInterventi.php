<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExtraAndPuliziaFondoToInterventi extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('interventi', [
            'extra' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'abbonamento_id',
            ],
            'pulizia_fondo' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'extra',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('interventi', ['extra', 'pulizia_fondo']);
    }
}
