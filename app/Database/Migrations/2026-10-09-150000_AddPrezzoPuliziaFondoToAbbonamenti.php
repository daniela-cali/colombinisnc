<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrezzoPuliziaFondoToAbbonamenti extends Migration
{
    public function up()
    {
        $this->forge->addColumn('abbonamenti', [
            'prezzo_pulizia_fondo' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'prezzo',
                'comment'    => 'prezzo della pulizia del fondo su richiesta, IVA esclusa; obbligatorio per i tipi con ha_pulizia_fondo, il rinnovo lo copia senza aumentarlo',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('abbonamenti', 'prezzo_pulizia_fondo');
    }
}
