<?php

/**
 * Helper di log generico: ogni "from" scrive nella propria sottocartella
 * dentro writable/custom_log/, un file per giorno.
 */

if (! function_exists('custom_log')) {
    /**
     * Scrive una riga (o più righe) di log nel file del giorno corrente,
     * nella sottocartella dedicata a $from. Crea la sottocartella al primo
     * utilizzo se non esiste ancora.
     *
     * @param string       $from    contesto del log (diventa il nome della sottocartella, es. 'abbonamenti_scaduti')
     * @param string|array $message messaggio da scrivere; se array, ogni elemento diventa una riga
     *                               (i sotto-array vengono uniti con '|')
     */
    function custom_log(string $from, string|array $message): bool
    {
        helper('filesystem');

        $dir = LOGPATH . $from . DIRECTORY_SEPARATOR;
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            log_message('error', 'Impossibile creare la cartella di log: ' . $dir);
            return false;
        }

        $path = $dir . date('Ymd') . '.log';

        $output = $message;
        if (is_array($message)) {
            $righe = [];
            foreach ($message as $riga) {
                $righe[] = is_array($riga) ? implode('|', $riga) : $riga;
            }
            $output = implode(PHP_EOL, $righe);
        }

        $timestamp = '[' . date('H:i:s') . '] ';

        if (! write_file($path, $timestamp . $output . PHP_EOL, 'ab')) {
            log_message('error', 'Impossibile scrivere sul file: ' . $path);
            return false;
        }

        return true;
    }
}
