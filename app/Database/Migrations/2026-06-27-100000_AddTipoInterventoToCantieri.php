<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoInterventoToCantieri extends Migration
{
    public function up(): void
    {
        // Tipo intervento predefinito del cantiere: pre-compila (in modo modificabile)
        // il campo Tipo quando si crea un nuovo intervento dalla scheda cantiere.
        // Nullable: un cantiere può non avere un tipo dominante.
        $this->forge->addColumn('cantieri', [
            "tipo_intervento_id INT UNSIGNED NULL COMMENT 'FK → tipi_intervento.id — tipo predefinito per i nuovi interventi del cantiere' AFTER tipo",
        ]);
        $this->db->query('ALTER TABLE cantieri ADD CONSTRAINT fk_cantieri_tipo_intervento FOREIGN KEY (tipo_intervento_id) REFERENCES tipi_intervento(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE cantieri DROP FOREIGN KEY fk_cantieri_tipo_intervento');
        $this->forge->dropColumn('cantieri', 'tipo_intervento_id');
    }
}
