<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbbonamentiPeriodi extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // Tabella abbonamenti_periodi
        // ----------------------------------------------------------------
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        ]);
        $this->forge->addField("abbonamento_id INT UNSIGNED NOT NULL COMMENT 'FK → abbonamenti.id'");
        $this->forge->addField("data_inizio DATE NOT NULL COMMENT 'Inizio del sotto-periodo di frequenza'");
        $this->forge->addField("data_fine DATE NOT NULL COMMENT 'Fine del sotto-periodo di frequenza'");
        $this->forge->addField("frequenza VARCHAR(20) NOT NULL COMMENT 'settimanale | quindicinale | mensile | bimestrale | trimestrale | semestrale | annuale'");
        $this->forge->addField("con_pulizia_fondo TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Opzione piscine: pulizia del fondo prevista in questo periodo'");
        $this->forge->addField("ordine INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Ordine di visualizzazione e generazione interventi'");
        $this->forge->addField([
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addKey('abbonamento_id');

        // CASCADE: quando un abbonamento viene eliminato, si eliminano i suoi periodi
        $this->forge->addForeignKey('abbonamento_id', 'abbonamenti', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('abbonamenti_periodi');

        // ----------------------------------------------------------------
        // Migrazione dati: porta il campo frequenza esistente nel primo periodo
        // ----------------------------------------------------------------
        $this->db->query("
            INSERT INTO abbonamenti_periodi
                (abbonamento_id, data_inizio, data_fine, frequenza, con_pulizia_fondo, ordine, created_at, updated_at)
            SELECT id, data_inizio, data_fine, frequenza, 0, 1, NOW(), NOW()
            FROM abbonamenti
            WHERE frequenza IS NOT NULL AND frequenza != ''
        ");

        // ----------------------------------------------------------------
        // Rimuove il campo frequenza da abbonamenti (ora è nei periodi)
        // ----------------------------------------------------------------
        $this->forge->dropColumn('abbonamenti', 'frequenza');
    }

    public function down(): void
    {
        // Ripristina il campo frequenza su abbonamenti
        $this->forge->addColumn('abbonamenti', [
            "frequenza VARCHAR(20) NULL COMMENT 'settimanale | quindicinale | mensile | bimestrale | trimestrale | semestrale | annuale' AFTER data_fine",
        ]);

        // Ripristina il valore dal primo periodo
        $this->db->query("
            UPDATE abbonamenti a
            SET a.frequenza = (
                SELECT ap.frequenza
                FROM abbonamenti_periodi ap
                WHERE ap.abbonamento_id = a.id
                ORDER BY ap.ordine ASC
                LIMIT 1
            )
        ");

        $this->forge->dropTable('abbonamenti_periodi', true);
    }
}
