<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;

class GeneraleController extends BaseController
{
    // Tipi intervento con le relative chiavi usate in settings (Interventi.durata_*)
    // Da sostituire con TipoInterventoModel quando arriverà la v0.7.0
    private const TIPI = [
        'sale'        => 'Consegna Sale',
        'filtri'      => 'Cambio Filtri',
        'piscine'     => 'Piscine',
        'addolcitori' => 'Addolcitori',
        'acquedotti'  => 'Acquedotti',
        'commerciale' => 'Richiesta Commerciale',
    ];

    /**
     * Mostra il form delle impostazioni generali (dati azienda, orari, logo).
     */
    public function index(): string
    {
        return view('impostazioni/index');
    }

    /**
     * Salva dati azienda, orari e logo caricato.
     */
    public function salva()
    {
        $post = $this->request->getPost();

        foreach (['ragione_sociale', 'partita_iva', 'codice_fiscale', 'telefono', 'email', 'sito', 'indirizzo', 'cap', 'citta', 'provincia'] as $key) {
            setting()->set('Azienda.' . $key, $post[$key] ?? null);
        }

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && ! $logo->hasMoved()) {
            $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = 'logo_azienda.' . $logo->getClientExtension();
            $logo->move($dir, $filename, true);
            setting()->set('Azienda.logo_path', 'uploads/logo/' . $filename);
        }

        foreach (['orario_inizio', 'orario_fine', 'pausa_inizio', 'pausa_fine'] as $key) {
            setting()->set('Tecnici.' . $key, $post[$key] ?? null);
        }

        return redirect()->to('impostazioni')->with('success', 'Impostazioni salvate.');
    }

    /**
     * Form parametri generali: dati sede, orari aziendali e durate interventi.
     */
    public function parametri(): string
    {
        return view('impostazioni/parametri', [
            'tipi' => self::TIPI,
        ]);
    }

    /**
     * Salva tutti i parametri generali nella tabella settings.
     * Il logo viene spostato in public/uploads/ e il percorso salvato come setting.
     */
    public function salvaParametri()
    {
        $post = $this->request->getPost();

        foreach (['sede_nome', 'sede_indirizzo', 'sede_citta', 'sede_cap', 'sede_lat', 'sede_lng', 'sede_telefono', 'sede_sito'] as $key) {
            setting()->set('Azienda.' . $key, $post[$key] ?? null);
        }

        foreach (['orario_inizio', 'orario_fine', 'pausa_inizio', 'pausa_fine'] as $key) {
            setting()->set('Azienda.' . $key, $post[$key] ?? null);
        }

        foreach (array_keys(self::TIPI) as $codice) {
            $val = $post['durata_' . $codice] ?? null;
            setting()->set('Interventi.durata_' . $codice, ($val !== null && $val !== '') ? (int) $val : null);
        }

        return redirect()->to('impostazioni/parametri')->with('success', 'Impostazioni salvate.');
    }

    /**
     * Carica o sostituisce il logo aziendale, indipendentemente dagli altri parametri.
     */
    public function cambiaLogo()
    {
        $logo = $this->request->getFile('sede_logo');

        if (! $logo || ! $logo->isValid() || $logo->hasMoved()) {
            return redirect()->to('impostazioni/parametri')->with('error', 'Nessun file selezionato o file non valido.');
        }

        $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'logo_azienda.' . $logo->getClientExtension();
        $logo->move($dir, $filename, true);
        setting()->set('Azienda.sede_logo_path', 'uploads/' . $filename);

        return redirect()->to('impostazioni/parametri')->with('success', 'Logo aggiornato.');
    }
}
