<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPropostaInviataToAbbonamenti extends Migration
{
    public function up()
    {
        $this->forge->addColumn('abbonamenti', [
            'proposta_inviata_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'proposta_generata_at',
                'comment' => 'ultimo invio della proposta per email, o Segna come inviata; NULL = non inviata',
            ],
            'proposta_inviata_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'proposta_inviata_at',
                'comment'  => 'utente che ha inviato la proposta o l\'ha segnata come inviata',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('abbonamenti', ['proposta_inviata_at', 'proposta_inviata_by']);
    }
}
