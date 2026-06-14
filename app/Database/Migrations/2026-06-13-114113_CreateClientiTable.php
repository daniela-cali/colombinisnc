<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientiTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            // eventuale chiave primaria del software contabile
            'id_external'         => ['type' => 'INT', 'null' => true],
            // codice univoco: dal software contabile per i clienti importati, INT-xxx per i nuovi
            'codice'              => ['type' => 'VARCHAR', 'constraint' => 15],
            // valori: societa, persona_fisica
            'tipo'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'societa'],
            'ragsoc'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'nome'                => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'cognome'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'piva'                => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'cfisc'               => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],
            'lat'                 => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'lng'                 => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'geocoded_at'         => ['type' => 'DATETIME', 'null' => true],
            'geocodifica_fallita' => ['type' => 'TINYINT', 'default' => 0],
            'indirizzo'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'citta'               => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'cap'                 => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'provincia'           => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'nazione'             => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'ITALIA'],
            'telefono'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'note'                => ['type' => 'TEXT', 'null' => true],
            'contatti'              => ['type' => 'TEXT', 'null' => true],
            // valori: -1 ovest, 0 limitrofe, 1 est — assegnazione manuale alla creazione
            'zona'                  => ['type' => 'TINYINT', 'null' => true, 'default' => null],
            // km in linea d'aria dalla sede, calcolato con haversine al salvataggio
            'distanza_sede'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true, 'default' => null],
            // FK → personale.id; il filtro per gruppo "tecnico" è applicativo, non a DB
            'tecnico_preferito_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'user_id'               => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'dt_import'             => ['type' => 'DATE', 'null' => true],
            'attivo'                => ['type' => 'TINYINT', 'default' => 1],
            'created_by'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codice');
        $this->forge->addKey('id_external');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('tecnico_preferito_id', 'personale', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('clienti');
    }

    public function down(): void
    {
        $this->forge->dropTable('clienti', true);
    }
}