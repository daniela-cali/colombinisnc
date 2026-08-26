<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Prepara il passaggio dei codici cliente interni al contatore atomico in `settings`.
 *
 * Due operazioni. La prima rinomina il prefisso da `INT-` a `CLI-`: `INT` significava
 * "interno" sui clienti e "intervento" sugli interventi, due cose diverse indicate dallo
 * stesso prefisso in due tabelle. Il momento è propizio perché in produzione l'anagrafica
 * è ancora vuota, quindi la conversione riguarda solo i dati di sviluppo.
 *
 * La seconda crea la riga del contatore, inizializzata al massimo numerico già assegnato —
 * zero se non c'è nessun cliente — così i codici ripartono da dove erano arrivati e nessuno
 * viene riassegnato. Ricalca SeedProgressivoInterventi, che fa lo stesso per gli interventi.
 *
 * Il padding passa da 3 a 4 cifre, allineandosi ai codici degli interventi. Con il contatore
 * la lunghezza non ha più effetto sulla correttezza — il prossimo numero non si ricava più
 * ordinando i codici esistenti — ma tenerla uniforme evita elenchi con codici di due formati.
 */
class ContatoreCodiciClienti extends Migration
{
    public function up(): void
    {
        $this->db->query("
            UPDATE clienti
            SET codice = CONCAT('CLI-', LPAD(CAST(SUBSTRING(codice, 5) AS UNSIGNED), 4, '0'))
            WHERE codice LIKE 'INT-%'
        ");

        $this->db->query("
            INSERT INTO settings (class, `key`, value, type, context, created_at, updated_at)
            SELECT 'Clienti', 'seq_CLI',
                   COALESCE(MAX(CAST(SUBSTRING(codice, 5) AS UNSIGNED)), 0),
                   'int', NULL, NOW(), NOW()
            FROM clienti
            WHERE codice LIKE 'CLI-%'
        ");

        // Numeratore morto: creato da SeedProgressivoInterventi a giugno e mai letto da
        // nessuna parte — i codici intervento usano le righe seq_*. Finché era invisibile non
        // dava fastidio; nella pagina dei numeratori comparirebbe accanto a quelli veri con
        // un valore privo di significato.
        $this->db->query("
            DELETE FROM settings WHERE class = 'Interventi' AND `key` = 'progressivo'
        ");
    }

    public function down(): void
    {
        // La riga 'progressivo' torna com'era: il valore era il massimo id degli interventi.
        $this->db->query("
            INSERT INTO settings (class, `key`, value, type, context, created_at, updated_at)
            SELECT 'Interventi', 'progressivo', COALESCE(MAX(id), 0), 'int', NULL, NOW(), NOW()
            FROM interventi
        ");

        $this->db->query("
            DELETE FROM settings WHERE class = 'Clienti' AND `key` = 'seq_CLI'
        ");

        $this->db->query("
            UPDATE clienti
            SET codice = CONCAT('INT-', LPAD(CAST(SUBSTRING(codice, 5) AS UNSIGNED), 3, '0'))
            WHERE codice LIKE 'CLI-%'
        ");
    }
}
