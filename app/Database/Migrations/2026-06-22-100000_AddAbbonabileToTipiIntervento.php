<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbbonabileToTipiIntervento extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tipi_intervento', [
            "abbonabile TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Può essere usato negli abbonamenti: 0=no, 1=sì' AFTER attivo",
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tipi_intervento', 'abbonabile');
    }
}
