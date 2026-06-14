<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCodiceEsternoToClienti extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('clienti', [
            'codice_esterno' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'codice',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('clienti', 'codice_esterno');
    }
}
