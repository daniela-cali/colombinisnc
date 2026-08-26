<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marca i clienti potenziali: chi ha ricevuto un preventivo o una proposta di abbonamento
 * ma non ha ancora accettato.
 *
 * Senza questa distinzione l'anagrafica si riempie di persone con cui l'azienda non ha mai
 * lavorato, indistinguibili dai clienti veri in elenchi, ricerche e tendine.
 *
 * È un flag e non un prefisso nel codice perché descrive uno stato che cambia: quando il
 * potenziale accetta diventa cliente, e il suo codice deve restare quello che era — è
 * l'identificatore già finito sul preventivo che gli è stato mandato. Convertirlo significa
 * aggiornare una colonna, non riscrivere un'identità.
 *
 * Default 0: un cliente nasce come cliente vero, e lo si marca potenziale esplicitamente
 * quando la scheda si apre per preparare un'offerta.
 */
class AddPotenzialeToClienti extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('clienti', [
            "potenziale TINYINT NOT NULL DEFAULT 0 COMMENT 'Ha ricevuto una proposta ma non è ancora cliente: 0=cliente, 1=potenziale' AFTER attivo",
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('clienti', 'potenziale');
    }
}
