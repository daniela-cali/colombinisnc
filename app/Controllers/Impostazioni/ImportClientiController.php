<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Libraries\ClientiAdhocImporter;
use App\Models\ClientiAdhocModel;
use RuntimeException;

/**
 * Import dei clienti legacy (gestionale Ad Hoc) nella tabella di parcheggio
 * `clienti_adhoc`, da cui vengono promossi a clienti veri uno alla volta.
 *
 * Vedi docs/spec/import_clienti_legacy_spec.md.
 */
class ImportClientiController extends BaseController
{
    /** Chiave del mapping colonne salvato fra un import e il successivo. */
    private const SETTING_MAPPING = 'Import.clienti_adhoc_mapping';

    /**
     * Landing: avanzamento della migrazione e form di caricamento del CSV.
     */
    public function index(): string
    {
        return view('impostazioni/import_clienti/index', [
            'contatori'    => (new ClientiAdhocModel())->contatori(),
            'help_sezione' => 'import_clienti',
        ]);
    }

    /**
     * Riceve il CSV, ne legge le intestazioni e passa al passo di mappatura.
     * Il file resta in WRITEPATH finché l'import non viene eseguito o abbandonato.
     */
    public function analizza()
    {
        $regole = [
            'csv_file' => [
                'rules'  => 'uploaded[csv_file]|ext_in[csv_file,csv,txt]|max_size[csv_file,10240]',
                'errors' => [
                    'uploaded' => 'Selezionare un file da caricare.',
                    'ext_in'   => 'Sono ammessi solo file .csv o .txt.',
                    'max_size' => 'Il file supera i 10 MB.',
                ],
            ],
        ];

        if (! $this->validate($regole)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('csv_file');

        $cartella = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR;
        if (! is_dir($cartella)) {
            mkdir($cartella, 0755, true);
        }

        $nomeFile = 'clienti_adhoc_' . time() . '.csv';
        $file->move($cartella, $nomeFile, true);
        $percorso = $cartella . $nomeFile;

        try {
            $headers = (new ClientiAdhocImporter())->leggiHeaders($percorso);
        } catch (RuntimeException $e) {
            @unlink($percorso);

            return redirect()->to('impostazioni/import-clienti')->with('error', $e->getMessage());
        }

        if ($headers === []) {
            @unlink($percorso);

            return redirect()->to('impostazioni/import-clienti')
                ->with('error', 'Il file non contiene una riga di intestazione leggibile.');
        }

        session()->set([
            'import_adhoc_file'    => $percorso,
            'import_adhoc_headers' => $headers,
        ]);

        return redirect()->to('impostazioni/import-clienti/mappa');
    }

    /**
     * Passo 2: associa ogni colonna del CSV a un campo del parcheggio.
     */
    public function mappa()
    {
        $headers = session()->get('import_adhoc_headers');

        if (! $headers) {
            return redirect()->to('impostazioni/import-clienti')
                ->with('error', 'Sessione scaduta: ricaricare il file.');
        }

        $salvato = setting(self::SETTING_MAPPING);

        return view('impostazioni/import_clienti/mappa', [
            'headers'        => $headers,
            'campi_dest'     => ClientiAdhocModel::CAMPI_IMPORTABILI,
            'mapping_salvato' => $salvato ? json_decode($salvato, true) : [],
            'help_sezione'   => 'import_clienti',
        ]);
    }

    /**
     * Passo 3: esegue l'import e memorizza il mapping per la volta successiva.
     */
    public function esegui()
    {
        $percorso = session()->get('import_adhoc_file');
        $headers  = session()->get('import_adhoc_headers');

        if (! $percorso || ! $headers || ! is_file($percorso)) {
            return redirect()->to('impostazioni/import-clienti')
                ->with('error', 'Sessione scaduta: ricaricare il file.');
        }

        $mapping = array_filter((array) $this->request->getPost('mapping'), static fn ($campo) => $campo !== '');

        // Senza il codice ogni riga verrebbe scartata: meglio dirlo subito che
        // restituire un risultato con "tutte saltate" e nessuna spiegazione.
        if (! in_array('codice', $mapping, true)) {
            return redirect()->back()
                ->with('error', 'Il campo "Codice" è obbligatorio: assegnarlo alla colonna corrispondente.');
        }

        try {
            $esito = (new ClientiAdhocImporter())->import($percorso, $mapping);
        } catch (RuntimeException $e) {
            return redirect()->to('impostazioni/import-clienti')->with('error', $e->getMessage());
        }

        // Il mapping si salva per nome di colonna, non per indice: l'export potrebbe
        // cambiare l'ordine delle colonne fra una versione e l'altra della query.
        $perNome = [];
        foreach ($mapping as $indice => $campo) {
            if (isset($headers[$indice])) {
                $perNome[$headers[$indice]] = $campo;
            }
        }
        setting()->set(self::SETTING_MAPPING, json_encode($perNome));

        @unlink($percorso);
        session()->remove(['import_adhoc_file', 'import_adhoc_headers']);

        return redirect()->to('impostazioni/import-clienti/risultato')->with('esito', $esito);
    }

    /**
     * Riepilogo dell'ultimo import. Raggiunta per redirect da esegui(): così un
     * refresh non ri-esegue il caricamento.
     */
    public function risultato()
    {
        $esito = session()->getFlashdata('esito');

        if (! $esito) {
            return redirect()->to('impostazioni/import-clienti');
        }

        return view('impostazioni/import_clienti/risultato', [
            'esito'        => $esito,
            'contatori'    => (new ClientiAdhocModel())->contatori(),
            'help_sezione' => 'import_clienti',
        ]);
    }

    /**
     * Elenco dei record ancora da promuovere, con ricerca full-text.
     */
    public function elenco(): string
    {
        $model = new ClientiAdhocModel();

        return view('impostazioni/import_clienti/elenco', [
            'records'      => $model->daMigrare(),
            'contatori'    => $model->contatori(),
            'help_sezione' => 'import_clienti',
        ]);
    }
}
