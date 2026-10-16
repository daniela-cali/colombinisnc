<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Casella email e firma di chi spedisce dal gestionale, in una tabella a parte e non su
 * personale: personale si legge con personale.* in molti punti (il layout a ogni pagina, le
 * tendine dei tecnici), e la firma pesa ~100 KB.
 */
class CreatePersonaleCaselleEmail extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'personale_id'  => ['type' => 'INT', 'unsigned' => true],
            'smtp_password' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'password della casella email del dipendente, cifrata con encryption.key',
            ],
            'firma_email' => [
                'type'    => 'MEDIUMTEXT',
                'null'    => true,
                'comment' => 'firma HTML delle email, con le immagini incorporate come data:',
            ],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addField('created_at DATETIME NULL');
        $this->forge->addField('updated_at DATETIME NULL');

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('personale_id');
        $this->forge->addForeignKey('personale_id', 'personale', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('personale_caselle_email');
    }

    public function down(): void
    {
        $this->forge->dropTable('personale_caselle_email', true);
    }
}
