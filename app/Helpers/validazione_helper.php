<?php

/**
 * Etichette leggibili nei messaggi di validazione.
 *
 * I messaggi di CI4 contengono il segnaposto {field}, che il framework sostituisce
 * con la `label` della regola se c'è, e altrimenti con il nome grezzo della colonna.
 * Senza etichette l'utente si vedeva scritto «Il campo "tipo_intervento_id" è
 * obbligatorio», che non vuol dire niente per chi non conosce il database.
 *
 * Le etichette non si scrivono nei controller: `BaseController::validate()` fa
 * passare da qui ogni array di regole, quindi un controller nuovo eredita il
 * comportamento senza doversene occupare.
 */

if (! function_exists('etichetta_campo')) {
    /**
     * Traduce il nome di un campo nell'etichetta da mostrare all'utente.
     *
     * Prima cerca nella mappa dei casi che una regola meccanica non può indovinare
     * (sigle, accenti, nomi che non somigliano al concetto); se non lo trova ripiega
     * sulla trasformazione automatica. Il ripiego è la parte importante: tiene la
     * mappa corta e fa sì che un campo aggiunto domani non mostri comunque mai il
     * nome grezzo della colonna.
     */
    function etichetta_campo(string $campo): string
    {
        static $etichette = [
            'tipo_intervento_id' => 'Tipo di intervento',
            'personale_id'       => 'Dipendente',
            'piva'               => 'Partita IVA',
            'cfisc'              => 'Codice fiscale',
            'cap'                => 'CAP',
            'citta'              => 'Città',
            'quantita'           => 'Quantità',
            'priorita'           => 'Priorità',
            'unita_misura'       => 'Unità di misura',
            'lat'                => 'Latitudine',
            'lng'                => 'Longitudine',
            'password_confirm'   => 'Conferma password',
            'data_ora_inizio'    => 'Data e ora di inizio',
            'data_ora_fine'      => 'Data e ora di fine',
            'durata_default'     => 'Durata predefinita',
            'referente_nome'     => 'Nome del referente',
            'referente_telefono' => 'Telefono del referente',
            'csv_file'           => 'File CSV',
            'sede_logo'          => 'Logo',
        ];

        // Regole col jolly (es. periodi.*.frequenza): conta l'ultimo segmento,
        // altrimenti l'etichetta si porterebbe dietro l'indice dell'array.
        $chiave = strrpos($campo, '.') !== false
            ? substr($campo, strrpos($campo, '.') + 1)
            : $campo;

        if (isset($etichette[$chiave])) {
            return $etichette[$chiave];
        }

        // Ripiego: cliente_id → Cliente, data_scadenza → Data scadenza.
        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $chiave)));
    }
}

if (! function_exists('regole_con_etichette')) {
    /**
     * Aggiunge la `label` a ogni regola di validazione che non ce l'ha già.
     *
     * Le regole scritte come stringa vengono convertite nella forma array di CI4.
     * Quelle già in forma array ricevono solo la `label`: il resto — `rules` e
     * soprattutto gli `errors` con i messaggi personalizzati — non va toccato.
     */
    function regole_con_etichette(array $regole): array
    {
        foreach ($regole as $campo => $regola) {
            if (is_array($regola)) {
                if (! isset($regola['label'])) {
                    $regola['label']  = etichetta_campo((string) $campo);
                    $regole[$campo]   = $regola;
                }
                continue;
            }

            $regole[$campo] = [
                'label' => etichetta_campo((string) $campo),
                'rules' => $regola,
            ];
        }

        return $regole;
    }
}
