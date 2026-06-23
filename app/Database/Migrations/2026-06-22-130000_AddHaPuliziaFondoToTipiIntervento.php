<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHaPuliziaFondoToTipiIntervento extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tipi_intervento', [
            "ha_pulizia_fondo TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Il tipo intervento prevede l\'opzione pulizia fondo (es. piscine)' AFTER prefisso_codice",
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tipi_intervento', 'ha_pulizia_fondo');
    }
}
