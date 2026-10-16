<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Converte documenti Word in PDF con LibreOffice, che rispetta intestazioni, loghi e
 * tabulazioni dei modelli (PhpWord con dompdf li perdeva). Come DocumentoWord non lascia file
 * sul server: lavora in una cartella temporanea che si cancella sempre.
 *
 * Richiede sul server libreoffice-writer-nogui, i caratteri Microsoft
 * (ttf-mscorefonts-installer) e poppler-utils per pdfinfo.
 */
class ConvertitorePdf
{
    /** Secondi oltre i quali LibreOffice si considera bloccato. */
    private const TIMEOUT = 120;

    /**
     * Converte i Word ricevuti come nome => contenuto e restituisce per ognuno il PDF con il
     * numero di pagine, sotto lo stesso nome con l'estensione .pdf. Più documenti passano da
     * un solo avvio di LibreOffice, che è la parte lenta della conversione.
     *
     * @param  array<string, string>                                     $documenti
     * @return array<string, array{contenuto: string, pagine: int}>
     *
     * @throws RuntimeException se LibreOffice non produce uno dei PDF
     */
    public function converti(array $documenti): array
    {
        $cartella = sys_get_temp_dir() . '/pdf_' . bin2hex(random_bytes(8));
        mkdir($cartella, 0700);

        try {
            // Nomi neutri su disco: apostrofi, & e spazi dei clienti non arrivano alla shell.
            $nomi = array_keys($documenti);
            $file = [];
            foreach ($nomi as $i => $nome) {
                file_put_contents("{$cartella}/{$i}.docx", $documenti[$nome]);
                $file[] = escapeshellarg("{$cartella}/{$i}.docx");
            }

            // Un profilo per conversione: due LibreOffice con lo stesso profilo si bloccano.
            $comando = 'timeout ' . self::TIMEOUT . ' soffice --headless --norestore'
                . ' ' . escapeshellarg("-env:UserInstallation=file://{$cartella}/profilo")
                . ' --convert-to pdf --outdir ' . escapeshellarg($cartella)
                . ' ' . implode(' ', $file) . ' 2>&1';
            exec('HOME=' . escapeshellarg($cartella) . ' ' . $comando, $output, $esito);

            $pdf = [];
            foreach ($nomi as $i => $nome) {
                $percorso = "{$cartella}/{$i}.pdf";
                if (! is_file($percorso)) {
                    log_message('error', 'ConvertitorePdf: {nome} non convertito (esito {esito}): {output}', [
                        'nome' => $nome, 'esito' => $esito, 'output' => implode(' | ', $output),
                    ]);
                    throw new RuntimeException('Conversione in PDF non riuscita per «' . $nome . '». Riprova; se si ripete, scarica il Word.');
                }

                $pdf[preg_replace('/\.docx$/i', '', $nome) . '.pdf'] = [
                    'contenuto' => file_get_contents($percorso),
                    'pagine'    => $this->pagine($percorso),
                ];
            }

            return $pdf;
        } finally {
            $this->cancella($cartella);
        }
    }

    /**
     * Le pagine del PDF, dalla riga «Pages:» di pdfinfo. Zero se non si leggono: il chiamante
     * che controlla un massimo non lo scambia per un documento troppo lungo.
     */
    private function pagine(string $percorso): int
    {
        exec('pdfinfo ' . escapeshellarg($percorso) . ' 2>/dev/null', $righe);

        foreach ($righe as $riga) {
            if (preg_match('/^Pages:\s+(\d+)/', $riga, $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /**
     * Cancella la cartella temporanea con tutto il contenuto, compreso il profilo di
     * LibreOffice, che ha sottocartelle.
     */
    private function cancella(string $cartella): void
    {
        if (! is_dir($cartella)) {
            return;
        }

        $voci = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cartella, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($voci as $voce) {
            $voce->isDir() ? rmdir($voce->getPathname()) : unlink($voce->getPathname());
        }
        rmdir($cartella);
    }
}
