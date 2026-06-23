<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbbonamentiTable extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // Tabella abbonamenti
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        ]);

        $this->forge->addField("cliente_id INT UNSIGNED NOT NULL COMMENT 'FK → clienti.id'");
        $this->forge->addField("tipo_intervento_id INT UNSIGNED NOT NULL COMMENT 'FK → tipi_intervento.id — stesso tipo per tutti gli interventi generati'");
        $this->forge->addField("abbonamento_precedente_id INT UNSIGNED NULL COMMENT 'FK → abbonamenti.id — catena storica rinnovi; CTE ricorsiva MySQL 8+ per ricostruirla'");
        $this->forge->addField("frequenza VARCHAR(20) NOT NULL COMMENT 'settimanale | quindicinale | mensile | bimestrale | trimestrale | semestrale | annuale'");
        $this->forge->addField("data_inizio DATE NOT NULL COMMENT 'tipicamente 01/01 — primo giorno di validità'");
        $this->forge->addField("data_fine DATE NOT NULL COMMENT 'tipicamente 31/12 — ultimo giorno di validità'");
        $this->forge->addField("durata_mesi INT UNSIGNED NULL COMMENT 'calcolato automaticamente dal model al salvataggio — non passare dal controller'");
        $this->forge->addField("prezzo DECIMAL(10,2) NULL COMMENT 'totale abbonamento per il periodo intero, non prezzo per visita'");
        $this->forge->addField("stato VARCHAR(20) NOT NULL DEFAULT 'attivo' COMMENT 'attivo | sospeso | scaduto | disdetto'");

        $this->forge->addField([
            'note'       => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('tipo_intervento_id');
        $this->forge->addKey('stato');
        $this->forge->addKey('data_fine');

        $this->forge->addForeignKey('cliente_id',                'clienti',        'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('tipo_intervento_id',        'tipi_intervento', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('abbonamento_precedente_id', 'abbonamenti',     'id', 'RESTRICT', 'SET NULL');

        $this->forge->createTable('abbonamenti');

        // ----------------------------------------------------------------
        // FK abbonamento_id su interventi
        // ----------------------------------------------------------------
        $this->forge->addColumn('interventi', [
            "abbonamento_id INT UNSIGNED NULL COMMENT 'FK → abbonamenti.id — NULL = standalone; NOT NULL = generato da abbonamento' AFTER impianto_id",
        ]);
        $this->db->query('ALTER TABLE interventi ADD CONSTRAINT fk_interventi_abbonamento FOREIGN KEY (abbonamento_id) REFERENCES abbonamenti(id) ON DELETE RESTRICT ON UPDATE CASCADE');

        // ----------------------------------------------------------------
        // Aggiorna commenti su interventi.stato e interventi.priorita
        // ----------------------------------------------------------------
        $this->db->query("ALTER TABLE interventi
            MODIFY COLUMN stato VARCHAR(30) NOT NULL DEFAULT 'da_pianificare'
                COMMENT 'da_pianificare | pianificato | in_corso | completato | annullato | sospeso'");

        $this->db->query("UPDATE interventi SET priorita = 'abbonamento' WHERE priorita = 'programmato'");

        $this->db->query("ALTER TABLE interventi
            MODIFY COLUMN priorita VARCHAR(30) NOT NULL DEFAULT 'normale'
                COMMENT 'abbonamento | normale | urgente'");
    }

    public function down(): void
    {
        $this->db->query("UPDATE interventi SET priorita = 'programmato' WHERE priorita = 'abbonamento'");

        $this->db->query("ALTER TABLE interventi
            MODIFY COLUMN priorita VARCHAR(30) NOT NULL DEFAULT 'normale'
                COMMENT 'programmato | normale | urgente'");

        $this->db->query("ALTER TABLE interventi
            MODIFY COLUMN stato VARCHAR(30) NOT NULL DEFAULT 'da_pianificare'
                COMMENT 'da_pianificare | pianificato | in_corso | completato | annullato'");

        $this->db->query('ALTER TABLE interventi DROP FOREIGN KEY fk_interventi_abbonamento');
        $this->forge->dropColumn('interventi', 'abbonamento_id');

        $this->forge->dropTable('abbonamenti', true);
    }
}
