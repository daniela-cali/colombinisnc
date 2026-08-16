<?php

namespace App\Libraries;

use App\Models\ClientiAdhocModel;
use RuntimeException;

/**
 * Lettura e caricamento di un CSV legacy nella tabella di parcheggio `clienti_adhoc`.
 *
 * Porting ripulito del `ClientiImportService` del vecchio progetto: si conservano
 * il rilevamento del separatore e la conversione di encoding, si abbandonano le
 * euristiche di pulizia del dato (split cognome/nome, inferenza del tipo) che sui
 * dati reali producevano più danni che benefici — la pulizia si fa ora nella query
 * di export, e quel che resta lo corregge l'operatore al momento della promozione.
 */
class ClientiAdhocImporter
{
    /**
     * Intestazioni del CSV, per il passo di mappatura colonne.
     *
     * @return list<string>
     *
     * @throws RuntimeException se il file non è leggibile
     */
    public function leggiHeaders(string $filePath): array
    {
        $handle = $this->apri($filePath);
        $riga   = fgetcsv($handle, 0, $this->separatore($filePath));
        fclose($handle);

        if ($riga === false || $riga === null) {
            return [];
        }

        $riga = array_map('trim', $this->convertiEncoding($riga));

        // Un CSV esportato da SQL Server o passato per Excel inizia spesso con il BOM
        // UTF-8 (EF BB BF): senza toglierlo la prima intestazione non combacerebbe mai
        // con il nome del campo, facendo fallire la pre-selezione solo sulla prima colonna.
        if (isset($riga[0])) {
            $riga[0] = preg_replace('/^\x{FEFF}/u', '', $riga[0]) ?? $riga[0];
        }

        return $riga;
    }

    /**
     * Legge il CSV, applica il mapping e scrive nel parcheggio.
     *
     * @param array<int|string,string> $mapping indice colonna CSV => campo di destinazione
     *
     * @return array{totale:int, inseriti:int, aggiornati:int, saltati:int}
     *
     * @throws RuntimeException se il file non è leggibile
     */
    public function import(string $filePath, array $mapping): array
    {
        $separatore = $this->separatore($filePath);
        $handle     = $this->apri($filePath);

        fgetcsv($handle, 0, $separatore); // scarta l'intestazione

        $righe   = [];
        $saltati = 0;

        while (($riga = fgetcsv($handle, 0, $separatore)) !== false) {
            $record = [];

            foreach ($mapping as $colonna => $campo) {
                if ($campo === '' || $campo === null) {
                    continue;
                }
                $valore           = $riga[(int) $colonna] ?? '';
                $record[$campo]   = trim($this->convertiValore($valore));
            }

            // Senza codice il record non è né identificabile né aggiornabile a un
            // ri-caricamento: si scarta e si conteggia, così l'operatore se ne accorge.
            if (($record['codice'] ?? '') === '') {
                $saltati++;
                continue;
            }

            foreach ($record as $campo => $valore) {
                if ($valore === '') {
                    $record[$campo] = null;
                }
            }

            // Codice ripetuto nello stesso file: vince l'ultima riga letta
            $righe[$record['codice']] = $record;
        }

        fclose($handle);

        $esito = (new ClientiAdhocModel())->importaBatch(array_values($righe));

        return [
            'totale'     => $esito['scritti'],
            'inseriti'   => $esito['inseriti'],
            'aggiornati' => $esito['aggiornati'],
            'saltati'    => $saltati,
        ];
    }

    /**
     * @return resource
     *
     * @throws RuntimeException
     */
    private function apri(string $filePath)
    {
        $handle = @fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Impossibile aprire il file CSV.');
        }

        return $handle;
    }

    /**
     * Deduce il separatore dalla prima riga contando le occorrenze.
     */
    private function separatore(string $filePath): string
    {
        $handle = $this->apri($filePath);
        $prima  = (string) fgets($handle);
        fclose($handle);

        return substr_count($prima, ';') >= substr_count($prima, ',') ? ';' : ',';
    }

    /**
     * @param list<string> $riga
     *
     * @return list<string>
     */
    private function convertiEncoding(array $riga): array
    {
        return array_map(fn ($valore) => $this->convertiValore($valore), $riga);
    }

    /**
     * Porta in UTF-8 i valori salvati in ISO-8859-1 (accenti dei gestionali datati).
     */
    private function convertiValore(?string $valore): string
    {
        $valore ??= '';

        if ($valore !== '' && ! mb_detect_encoding($valore, 'UTF-8', true)) {
            $valore = mb_convert_encoding($valore, 'UTF-8', 'ISO-8859-1');
        }

        return $valore;
    }
}
