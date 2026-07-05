<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientiModel extends Model
{
    protected $table         = 'clienti';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id_external', 'codice', 'codice_esterno', 'tipo', 'ragsoc', 'nome', 'cognome',
        'piva', 'cfisc',
        'lat', 'lng', 'geocoded_at', 'geocodifica_fallita',
        'indirizzo', 'citta', 'cap', 'provincia', 'nazione',
        'telefono', 'email', 'note', 'contatti',
        'zona', 'distanza_sede', 'tecnico_preferito_id',
        'user_id', 'dt_import', 'attivo',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: societa, persona_fisica
    const TIPO_SOCIETA        = 'societa';
    const TIPO_PERSONA_FISICA = 'persona_fisica';

    // valori: -1 Ventimiglia (da Andora verso Francia), 0 Ceriale (da Andora a Loano), 1 Savona (da Loano in poi)
    const ZONA_VENTIMIGLIA = -1;
    const ZONA_CERIALE     =  0;
    const ZONA_SAVONA      =  1;

    const ZONE_LABEL = [
        -1 => 'Ventimiglia',
         0 => 'Ceriale',
         1 => 'Savona',
    ];

    // Nazioni proposte nella select del form cliente (zona di confine Italia/Francia).
    // Aggiungerne una nuova: basta aggiungerla qui, la select in nuovo.php/edit.php la recepisce da sola.
    const NAZIONI_PREDEFINITE = ['ITALIA', 'FRANCIA'];

    /**
     * Imposta created_by/updated_by e ricalcola distanza_sede se lat/lng sono presenti.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        // created_by si imposta solo all'inserimento: CI4 passa $data['id'] solo negli update
        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        // Denominazione e città sempre in maiuscolo per coerenza visiva e ricerche
        foreach (['ragsoc', 'cognome', 'nome', 'citta', 'provincia'] as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] !== '') {
                $data['data'][$campo] = mb_strtoupper($data['data'][$campo], 'UTF-8');
            }
        }

        // Campi nullable che arrivano come "" dal form quando non compilati:
        // MySQL rifiuterebbe "" su colonne INT, DECIMAL e DATETIME dichiarate NULL.
        $nullabili = ['tecnico_preferito_id', 'user_id', 'zona', 'lat', 'lng', 'distanza_sede', 'geocoded_at'];
        foreach ($nullabili as $campo) {
            if (isset($data['data'][$campo]) && $data['data'][$campo] === '') {
                $data['data'][$campo] = null;
            }
        }

        // Lettura coordinata: dopo la nullificazione sopra, "" è già null.
        // Il cast a float è sicuro perché arriva solo se il valore è non-null.
        $lat = isset($data['data']['lat']) ? (float) $data['data']['lat'] : null;
        $lng = isset($data['data']['lng']) ? (float) $data['data']['lng'] : null;

        if ($lat && $lng) {
            $sedeLat = (float) setting('Azienda.sede_lat');
            $sedeLng = (float) setting('Azienda.sede_lng');

            // Ricalcola distanza_sede solo se anche la sede è già geocodificata
            if ($sedeLat && $sedeLng) {
                $data['data']['distanza_sede'] = $this->haversine($sedeLat, $sedeLng, $lat, $lng);
            }

            // Auto-assegna zona dalla longitudine solo se non è già impostata dall'utente.
            // Le soglie vengono da Parametri → Azienda.zona_lng_ovest / zona_lng_est.
            // Soglie: Ventimiglia < zona_lng_ovest <= Ceriale <= zona_lng_est < Savona
            if (! isset($data['data']['zona']) || $data['data']['zona'] === null) {
                $sogliaVentimiglia = setting('Azienda.zona_lng_ovest');
                $sogliaSavona      = setting('Azienda.zona_lng_est');

                if ($sogliaVentimiglia !== null && $sogliaSavona !== null) {
                    if ($lng < (float) $sogliaVentimiglia) {
                        $data['data']['zona'] = self::ZONA_VENTIMIGLIA;
                    } elseif ($lng > (float) $sogliaSavona) {
                        $data['data']['zona'] = self::ZONA_SAVONA;
                    } else {
                        $data['data']['zona'] = self::ZONA_CERIALE;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Distanza in km tra due coordinate geografiche (formula haversine).
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    /**
     * Lista completa con denominazione calcolata (CASE SQL) e nome del tecnico preferito.
     * Il campo `denominazione` è ragione sociale per le società,
     * cognome + nome per le persone fisiche — già ordinato alfabeticamente.
     * `num_interventi` conta solo gli interventi manuali aperti (abbonamento_id IS NULL, stato non completato/annullato):
     * gli abbonamenti generano decine di figli upfront e gonfierebbero il badge; i completati/annullati
     * non rappresentano lavoro ancora da fare.
     */
    public function elencoCompleto(): array
    {
        return $this->select("clienti.*,
            CASE WHEN clienti.tipo = 'persona_fisica'
                 THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                 ELSE clienti.ragsoc
            END AS denominazione,
            TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_preferito_nome,
            (SELECT COUNT(*) FROM interventi WHERE interventi.cliente_id = clienti.id AND interventi.abbonamento_id IS NULL AND interventi.stato NOT IN ('completato','annullato')) AS num_interventi")
            ->join('personale p', 'p.id = clienti.tecnico_preferito_id', 'left')
            ->orderBy("CASE WHEN clienti.tipo = 'persona_fisica'
                THEN TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))
                ELSE clienti.ragsoc END")
            ->findAll();
    }

    /**
     * Genera il prossimo codice INT-xxx per clienti non presenti nel software contabile.
     */
    public function generaCodice(): string
    {
        $row = $this->select('codice')
            ->like('codice', 'INT-', 'after')
            ->orderBy('codice', 'DESC')
            ->first();

        if (! $row) {
            return 'INT-001';
        }

        $numero = (int) substr($row['codice'], 4);
        return 'INT-' . str_pad($numero + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Restituisce la denominazione da mostrare in lista:
     * ragione sociale per società, cognome e nome per persone fisiche.
     */
    public static function denominazione(array $cliente): string
    {
        if ($cliente['tipo'] === self::TIPO_PERSONA_FISICA) {
            return trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''));
        }

        return $cliente['ragsoc'] ?? '';
    }
}
