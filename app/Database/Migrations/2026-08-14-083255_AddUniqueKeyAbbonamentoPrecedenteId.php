<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueKeyAbbonamentoPrecedenteId extends Migration
{
    public function up()
    {
        $this->forge->addUniqueKey('abbonamento_precedente_id', 'uq_abbonamenti_abbonamento_precedente_id');
        $this->forge->processIndexes('abbonamenti');
    }

    public function down()
    {
        $this->forge->dropKey('abbonamenti', 'uq_abbonamenti_abbonamento_precedente_id');
    }
}
