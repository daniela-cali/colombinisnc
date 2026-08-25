<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Porta la foreign key `abbonamenti.abbonamento_precedente_id` da ON DELETE SET NULL a
 * ON DELETE RESTRICT.
 *
 * È l'unica chiave esterna che cede su un collegamento non ricostruibile. `interventi →
 * abbonamenti` è già RESTRICT, secondo il criterio per cui il database rifiuta e
 * l'applicazione spiega; `interventi_materiali → interventi` e `interventi_note → interventi`
 * sono invece CASCADE — verificato su `information_schema`, non dedotto dalle migration, dove
 * l'ordine degli argomenti di `addForeignKey()` si legge male. Lì la cascata è corretta: sono
 * righe che appartengono all'intervento e non hanno senso senza di lui, mentre un abbonamento
 * precedente è storia a sé.
 *
 * Con SET NULL, eliminare un abbonamento che è stato rinnovato riuscirebbe azzerando in
 * silenzio il collegamento sul successore: il rinnovo comparirebbe come un contratto nato
 * dal nulla, e la catena storica di quel cliente perderebbe un anello senza che nessuno se
 * ne accorga. L'informazione non è ridondata da nessuna parte, quindi il danno sarebbe
 * anche irreversibile.
 *
 * Vedi docs/spec/abbonamenti_annulla_accettazione_spec.md, decisione 9.
 */
class AbbonamentoPrecedenteRestrict extends Migration
{
    /**
     * L'ON UPDATE resta RESTRICT com'era: cambia solo la regola di cancellazione.
     * Firma: addForeignKey($campo, $tabella, $campoRif, $onUpdate, $onDelete).
     */
    public function up(): void
    {
        $this->forge->dropForeignKey('abbonamenti', 'abbonamenti_abbonamento_precedente_id_foreign');

        $this->forge->addForeignKey('abbonamento_precedente_id', 'abbonamenti', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->processIndexes('abbonamenti');
    }

    /**
     * Ripristina ON DELETE SET NULL: lo stato precedente esatto, non quello corretto.
     */
    public function down(): void
    {
        $this->forge->dropForeignKey('abbonamenti', 'abbonamenti_abbonamento_precedente_id_foreign');

        $this->forge->addForeignKey('abbonamento_precedente_id', 'abbonamenti', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->processIndexes('abbonamenti');
    }
}
