<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNomeCognomeToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'username',
            ],
            'cognome' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'nome',
            ],
            'telefono' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'cognome',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['nome', 'cognome', 'telefono']);
    }
}
