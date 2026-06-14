<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUltimaVersioneVistaToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'ultima_versione_vista' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'active',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'ultima_versione_vista');
    }
}
