<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientiAdhoc extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            // Campi anagrafici: copia grezza del CSV legacy (gestionale Ad Hoc).
            // Nessun vincolo di contenuto e dimensioni più larghe di quelle di `clienti`:
            // il parcheggio deve accettare tutto, la validazione avviene alla promozione.
            'codice'    => ['type' => 'VARCHAR', 'constraint' => 30],
            'tipo'      => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'ragsoc'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cognome'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nome'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'piva'      => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'cfisc'     => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'indirizzo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'citta'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'cap'       => ['type' => 'VARCHAR', 'constraint' => 15,  'null' => true],
            'provincia' => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => true],
            'nazione'   => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true],
            'telefono'  => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true],
            'email'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'note'      => ['type' => 'TEXT', 'null' => true],

            // Stato della migrazione verso `clienti`
            'importato'   => ['type' => 'TINYINT', 'default' => 0],
            'cliente_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'imported_at' => ['type' => 'DATETIME', 'null' => true],

            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        // Rende il ri-caricamento dello stesso export un aggiornamento (upsertBatch)
        // invece di una duplicazione: l'export verrà rifatto più volte affinando la query.
        $this->forge->addUniqueKey('codice');
        $this->forge->addKey('ragsoc');
        $this->forge->addKey('importato');
        // Se un cliente promosso viene cancellato il parcheggio perde il puntatore
        // ma non la memoria di essere stato migrato (`importato` resta 1).
        // Attenzione all'ordine degli argomenti di addForeignKey(): onUpdate PRIMA di
        // onDelete. Invertirli qui significherebbe cancellare a cascata la riga di
        // parcheggio insieme al cliente, perdendo la traccia della migrazione.
        $this->forge->addForeignKey('cliente_id', 'clienti', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('clienti_adhoc');
    }

    public function down(): void
    {
        $this->forge->dropTable('clienti_adhoc', true);
    }
}
