<?php

namespace App\Database\Seeds;

use App\Models\InterventiModel;
use CodeIgniter\Database\Seeder;

/**
 * Inserisce dati di test visibili nella dashboard per oggi.
 * Tutti i record hanno descrizione prefissata con [TEST].
 * Rieseguire il seeder per aggiornare la data dei record al giorno corrente.
 * Cleanup manuale: DELETE FROM interventi WHERE descrizione LIKE '[TEST]%';
 *                  DELETE FROM abbonamenti WHERE note = '[TEST]';
 */
class DashboardTestSeeder extends Seeder
{
    public function run(): void
    {
        // Pulizia record di test precedenti
        $this->db->table('interventi')->like('descrizione', '[TEST]', 'after')->delete();
        $this->db->table('abbonamenti')->where('note', '[TEST]')->delete();

        $oggi = date('Y-m-d');
        $now  = date('Y-m-d H:i:s');

        // Interventi pianificati oggi — generaCodice() viene chiamata da normalizza()
        $interventiOggi = [
            ['cliente_id' => 1, 'tecnico_id' => 2, 'tipo_intervento_id' => 4, 'descrizione' => '[TEST] Controllo addolcitore',   'stato' => 'pianificato',    'priorita' => 'normale', 'urgenza' => 0, 'data_pianificata' => $oggi . ' 09:00:00'],
            ['cliente_id' => 5, 'tecnico_id' => 2, 'tipo_intervento_id' => 1, 'descrizione' => '[TEST] Consegna sale mensile',   'stato' => 'pianificato',    'priorita' => 'normale', 'urgenza' => 0, 'data_pianificata' => $oggi . ' 11:30:00'],
            ['cliente_id' => 2, 'tecnico_id' => 4, 'tipo_intervento_id' => 2, 'descrizione' => '[TEST] Cambio filtri osmosi',    'stato' => 'pianificato',    'priorita' => 'normale', 'urgenza' => 0, 'data_pianificata' => $oggi . ' 10:00:00'],
            ['cliente_id' => 8, 'tecnico_id' => 5, 'tipo_intervento_id' => 3, 'descrizione' => '[TEST] Manutenzione piscina',   'stato' => 'pianificato',    'priorita' => 'normale', 'urgenza' => 0, 'data_pianificata' => $oggi . ' 08:30:00'],
            ['cliente_id' => 6, 'tecnico_id' => 5, 'tipo_intervento_id' => 4, 'descrizione' => '[TEST] Revisione addolcitore',  'stato' => 'pianificato',    'priorita' => 'normale', 'urgenza' => 0, 'data_pianificata' => $oggi . ' 14:30:00'],
            // 2 urgenti da pianificare
            ['cliente_id' => 9, 'tecnico_id' => 2, 'tipo_intervento_id' => 3, 'descrizione' => '[TEST] Piscina guasta — urgente', 'stato' => 'da_pianificare', 'priorita' => 'normale', 'urgenza' => 1, 'data_pianificata' => null],
            ['cliente_id' => 7, 'tecnico_id' => 5, 'tipo_intervento_id' => 4, 'descrizione' => '[TEST] Addolcitore in avaria',   'stato' => 'da_pianificare', 'priorita' => 'normale', 'urgenza' => 1, 'data_pianificata' => null],
        ];

        $model = model(InterventiModel::class);
        foreach ($interventiOggi as $row) {
            $model->insert($row);
        }

        // 2 abbonamenti in scadenza entro 30 giorni (note=[TEST] per cleanup)
        $abbonamenti = [
            ['cliente_id' => 1, 'tipo_intervento_id' => 4, 'data_inizio' => '2025-07-05', 'data_fine' => date('Y-m-d', strtotime('+8 days')),  'prezzo' => 480.00, 'stato' => 'attivo', 'note' => '[TEST]'],
            ['cliente_id' => 6, 'tipo_intervento_id' => 3, 'data_inizio' => '2025-07-20', 'data_fine' => date('Y-m-d', strtotime('+20 days')), 'prezzo' => 650.00, 'stato' => 'attivo', 'note' => '[TEST]'],
        ];

        foreach ($abbonamenti as $row) {
            $this->db->table('abbonamenti')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        echo 'DashboardTestSeeder: 5 interventi oggi, 2 urgenti, 2 abbonamenti in scadenza.' . PHP_EOL;
        echo 'Cleanup: DELETE FROM interventi WHERE descrizione LIKE \'[TEST]%\';' . PHP_EOL;
        echo '         DELETE FROM abbonamenti WHERE note = \'[TEST]\';' . PHP_EOL;
    }
}
