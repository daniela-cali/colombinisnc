<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApparecchiatureToAbbonamenti extends Migration
{
    public function up()
    {
        $this->forge->addColumn('abbonamenti', [
            'apparecchiature' => [
                'type'    => 'TEXT',
                'null'    => true,
                'after'   => 'operazioni_incluse',
                'comment' => 'una riga per apparecchiatura (es. N. 1 ADDOLCITORE), obbligatorio per i tipi della categoria addolcitori',
            ],
            'proposta_generata_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'modalita_pagamento',
                'comment' => 'ultima generazione della proposta in Word, non aggiorna updated_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('abbonamenti', ['apparecchiature', 'proposta_generata_at']);
    }
}
