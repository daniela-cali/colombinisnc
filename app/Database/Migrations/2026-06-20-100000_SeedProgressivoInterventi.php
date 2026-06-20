<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedProgressivoInterventi extends Migration
{
    public function up(): void
    {
        // Inserisce il contatore progressivo per i codici intervento nella tabella settings.
        // Il valore iniziale è il MAX(id) degli interventi esistenti, così il prossimo
        // codice generato è sempre maggiore di tutti quelli già assegnati.
        // A go-live il DB verrà svuotato: MAX(id) sarà NULL → COALESCE restituisce 0
        // e il primo intervento riceverà IV-0001.
        $this->db->query("
            INSERT INTO settings (class, `key`, value, type, context, created_at, updated_at)
            SELECT 'Interventi', 'progressivo', COALESCE(MAX(id), 0), 'int', NULL, NOW(), NOW()
            FROM interventi
        ");
    }

    public function down(): void
    {
        $this->db->query("
            DELETE FROM settings WHERE class = 'Interventi' AND `key` = 'progressivo'
        ");
    }
}
