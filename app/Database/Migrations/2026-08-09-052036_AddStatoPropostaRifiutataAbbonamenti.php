<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatoPropostaRifiutataAbbonamenti extends Migration
{
    public function up()
    {
        $this->forge->addColumn('abbonamenti', [
            'operazioni_incluse' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'durata_mesi',
                'comment' => 'derivato da tipi_intervento.operazioni_standard, contiene le effettive operazioni dell\'abbonamento creato'
            ],
            'modalita_pagamento' => [
                'type' => 'VARCHAR(255)',
                'null' => true,
                'after' => 'operazioni_incluse',
            ],
        ]);

        $this->forge->modifyColumn('abbonamenti', [
            'stato' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'proposta',
                'comment' => 'proposta | attivo | sospeso | scaduto | disdetto | rifiutata'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('abbonamenti', ['operazioni_incluse', 'modalita_pagamento']);
        $this->forge->modifyColumn('abbonamenti', [
            'stato' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'attivo',
                'comment' => 'attivo | sospeso | scaduto | disdetto'
            ]
        ]);
    }
}
