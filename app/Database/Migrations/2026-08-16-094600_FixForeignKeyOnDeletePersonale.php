<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Corregge la foreign key `personale.user_id` → `users`, creata con gli argomenti di
 * addForeignKey() invertiti (stessa causa di FixForeignKeyOnDeleteClienti).
 *
 * Effetto prima del fix: cancellare un account utente cancellava a cascata la scheda
 * dipendente collegata, vanificando la scelta di progetto di tenere `personale` separata
 * da `users` (vedi docs/ANALISI.md §8). Un dipendente esiste come persona anche senza
 * un account per accedere al gestionale: ON DELETE SET NULL.
 */
class FixForeignKeyOnDeletePersonale extends Migration
{
    public function up(): void
    {
        $this->forge->dropForeignKey('personale', 'personale_user_id_foreign');

        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('personale');
    }

    /**
     * Ripristina la regola originale — difettosa, ma è lo stato precedente esatto:
     * un rollback deve riportare lo schema com'era, non a come avrebbe dovuto essere.
     */
    public function down(): void
    {
        $this->forge->dropForeignKey('personale', 'personale_user_id_foreign');

        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('personale');
    }
}
