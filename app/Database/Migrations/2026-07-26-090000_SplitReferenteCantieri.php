<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SplitReferenteCantieri extends Migration
{
    public function up(): void
    {
        // Il referente esiste apposta per essere una persona diversa dal cliente
        // (custode, amministratore, intermediario): niente fallback sul telefono
        // cliente quando referente_telefono è NULL, gestito lato applicazione.
        $this->forge->modifyColumn('cantieri', [
            'referente' => [
                'name'       => 'referente_nome',
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'persona di contatto operativa: nome ed eventuale ruolo',
            ],
        ]);

        $this->forge->addColumn('cantieri', [
            'referente_telefono' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'referente_nome',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cantieri', 'referente_telefono');

        $this->forge->modifyColumn('cantieri', [
            'referente_nome' => [
                'name'       => 'referente',
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'persona di contatto operativa: nome, ruolo, telefono',
            ],
        ]);
    }
}
