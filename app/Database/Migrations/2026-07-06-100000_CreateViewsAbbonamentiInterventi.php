<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * View di sola lettura che uniscono abbonamenti/interventi alla denominazione cliente,
 * per semplificare le query manuali a DB (DBeaver ecc.) senza dover riscrivere ogni
 * volta i JOIN con clienti, tipi_intervento e personale.
 */
class CreateViewsAbbonamentiInterventi extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE VIEW v_abbonamenti_clienti AS
            SELECT
                a.id AS abbonamento_id,
                a.stato AS abbonamento_stato,
                CASE WHEN a.data_fine < CURDATE() AND a.stato = 'attivo'
                     THEN 'scaduto' ELSE a.stato END AS abbonamento_stato_calcolato,
                a.data_inizio, a.data_fine, a.durata_mesi, a.prezzo,
                ti.nome AS tipo_intervento_nome,
                c.id AS cliente_id,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc END AS cliente_denominazione,
                c.citta AS cliente_citta,
                c.telefono AS cliente_telefono,
                c.email AS cliente_email
            FROM abbonamenti a
            JOIN clienti c ON c.id = a.cliente_id
            LEFT JOIN tipi_intervento ti ON ti.id = a.tipo_intervento_id
        ");

        $this->db->query("
            CREATE VIEW v_abbonamenti_clienti_interventi AS
            SELECT
                a.id AS abbonamento_id,
                a.stato AS abbonamento_stato,
                CASE WHEN a.data_fine < CURDATE() AND a.stato = 'attivo'
                     THEN 'scaduto' ELSE a.stato END AS abbonamento_stato_calcolato,
                c.id AS cliente_id,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc END AS cliente_denominazione,
                c.citta AS cliente_citta,
                c.telefono AS cliente_telefono,
                ti.nome AS tipo_intervento_nome,
                i.id AS intervento_id,
                i.codice AS intervento_codice,
                i.data_scadenza,
                i.data_pianificata,
                i.stato AS intervento_stato,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome
            FROM abbonamenti a
            JOIN clienti c ON c.id = a.cliente_id
            LEFT JOIN tipi_intervento ti ON ti.id = a.tipo_intervento_id
            LEFT JOIN interventi i ON i.abbonamento_id = a.id
            LEFT JOIN personale p ON p.id = i.tecnico_id
        ");

        $this->db->query("
            CREATE VIEW v_interventi_clienti AS
            SELECT
                i.id AS intervento_id,
                i.codice AS intervento_codice,
                i.stato AS intervento_stato,
                i.priorita,
                i.data_pianificata,
                i.data_scadenza,
                i.descrizione,
                i.urgenza,
                c.id AS cliente_id,
                CASE WHEN c.tipo = 'persona_fisica'
                     THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                     ELSE c.ragsoc END AS cliente_denominazione,
                c.citta AS cliente_citta,
                c.telefono AS cliente_telefono,
                ti.nome AS tipo_intervento_nome,
                TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                ca.titolo AS cantiere_titolo
            FROM interventi i
            JOIN clienti c ON c.id = i.cliente_id
            LEFT JOIN tipi_intervento ti ON ti.id = i.tipo_intervento_id
            LEFT JOIN personale p ON p.id = i.tecnico_id
            LEFT JOIN cantieri ca ON ca.id = i.cantiere_id
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP VIEW IF EXISTS v_interventi_clienti');
        $this->db->query('DROP VIEW IF EXISTS v_abbonamenti_clienti_interventi');
        $this->db->query('DROP VIEW IF EXISTS v_abbonamenti_clienti');
    }
}
