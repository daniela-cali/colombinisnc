<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLuogoReferenteGeoToCantieri extends Migration
{
    public function up(): void
    {
        // Luogo, referente e posizione geografica del cantiere: tutti nullable, con
        // fallback sui campi omonimi del cliente quando NULL (gestito lato applicazione,
        // non qui) — copre il caso normale (cantiere a casa del cliente) senza attrito.
        $this->forge->addColumn('cantieri', [
            "indirizzo VARCHAR(255) NULL COMMENT 'luogo del cantiere — NULL = indirizzo del cliente' AFTER titolo",
            "citta VARCHAR(100) NULL COMMENT 'città del cantiere — NULL = città del cliente' AFTER indirizzo",
            "referente VARCHAR(150) NULL COMMENT 'persona di contatto operativa: nome, ruolo, telefono' AFTER citta",
            "lat DECIMAL(10,7) NULL COMMENT 'posizione del cantiere — NULL = posizione del cliente' AFTER referente",
            "lng DECIMAL(10,7) NULL AFTER lat",
            "geocoded_at DATETIME NULL COMMENT 'ultima geocodifica riuscita o correzione manuale del pin' AFTER lng",
            "geocodifica_fallita TINYINT NOT NULL DEFAULT 0 COMMENT 'geocodifica automatica tentata e non riuscita' AFTER geocoded_at",
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cantieri', [
            'indirizzo', 'citta', 'referente', 'lat', 'lng', 'geocoded_at', 'geocodifica_fallita',
        ]);
    }
}
