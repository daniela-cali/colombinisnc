<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Corregge le due foreign key di `clienti` create con gli argomenti di addForeignKey()
 * invertiti.
 *
 * La firma è addForeignKey($campo, $tabella, $campoRif, $onUpdate, $onDelete): scrivendo
 * 'SET NULL', 'CASCADE' si ottiene ON UPDATE SET NULL + ON DELETE CASCADE, cioè l'opposto
 * dell'intenzione. Conseguenze reali prima di questo fix:
 *
 *  - cancellare un dipendente cancellava a cascata TUTTI i clienti che lo avevano come
 *    tecnico preferito — perdita di dati, raggiungibile da PersonaleController::delete();
 *  - cancellare un utente cancellava il cliente collegato.
 *
 * In entrambi i casi il comportamento atteso è che il cliente sopravviva perdendo solo il
 * riferimento: ON DELETE SET NULL.
 */
class FixForeignKeyOnDeleteClienti extends Migration
{
    public function up(): void
    {
        $this->forge->dropForeignKey('clienti', 'clienti_tecnico_preferito_id_foreign');
        $this->forge->dropForeignKey('clienti', 'clienti_user_id_foreign');

        $this->forge->addForeignKey('tecnico_preferito_id', 'personale', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('clienti');
    }

    /**
     * Ripristina le regole originali — difettose, ma è lo stato precedente esatto:
     * un rollback deve riportare lo schema com'era, non a come avrebbe dovuto essere.
     */
    public function down(): void
    {
        $this->forge->dropForeignKey('clienti', 'clienti_tecnico_preferito_id_foreign');
        $this->forge->dropForeignKey('clienti', 'clienti_user_id_foreign');

        $this->forge->addForeignKey('tecnico_preferito_id', 'personale', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('clienti');
    }
}
