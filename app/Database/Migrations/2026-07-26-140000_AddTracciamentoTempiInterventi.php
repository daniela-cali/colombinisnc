<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTracciamentoTempiInterventi extends Migration
{
    public function up(): void
    {
        // Nullable, valorizzati solo dai bottoni "Inizio lavoro"/"Completa intervento"
        // (mobile_ux_spec.md §2.5): nessuna funzionalità dipende da questi valori oggi,
        // servono a calcolare la durata media degli interventi quando ci sarà abbastanza
        // storico. Se un intervento salta da pianificato a completato senza mai passare
        // da "in corso", data_inizio_lavoro resta NULL: dato onesto, non un timestamp fittizio.
        $this->forge->addColumn('interventi', [
            'data_inizio_lavoro' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'data_pianificata',
            ],
            'data_completamento' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'data_inizio_lavoro',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('interventi', ['data_inizio_lavoro', 'data_completamento']);
    }
}
