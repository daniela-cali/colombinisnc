<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrefissoCodiceToTipiIntervento extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tipi_intervento', [
            "prefisso_codice VARCHAR(10) NULL DEFAULT NULL COMMENT 'Prefisso 3 caratteri per codici interventi da abbonamento (es. PIS, ADD). NULL = usa INT' AFTER abbonabile",
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tipi_intervento', 'prefisso_codice');
    }
}
