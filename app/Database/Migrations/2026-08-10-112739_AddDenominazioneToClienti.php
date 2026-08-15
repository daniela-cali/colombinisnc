<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDenominazioneToClienti extends Migration
{
    public function up()
    {
        $this->forge->addColumn('clienti',
        "denominazione VARCHAR(255) GENERATED ALWAYS AS(
        CASE WHEN tipo = 'persona_fisica'
            THEN TRIM(CONCAT_WS(' ', cognome, nome))
            ELSE ragsoc
        END)
        STORED 
        COMMENT 'generata: cognome+nome se persona_fisica, altrimenti ragsoc'");
    }

    public function down()
    {
        $this->forge->dropColumn('clienti', 'denominazione');
    }
}
