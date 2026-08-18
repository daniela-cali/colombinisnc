<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;

class GeneraleController extends BaseController
{
    /**
     * Mostra il form delle impostazioni generali (dati azienda, orari, logo).
     */
    public function index(): string
    {
        return view('impostazioni/index', ['help_sezione' => 'impostazioni']);
    }

    /**
     * Form parametri generali: dati sede e orari aziendali.
     */
    public function parametri(): string
    {
        return view('impostazioni/parametri');
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

        // Soglie di longitudine per l'assegnazione automatica della zona cliente
        foreach (['zona_lng_ovest', 'zona_lng_est'] as $key) {
            $val = $post[$key] ?? null;
            setting()->set('Azienda.' . $key, ($val !== null && $val !== '') ? (float) $val : null);
        }

        foreach (['orario_inizio', 'orario_fine', 'pausa_inizio', 'pausa_fine'] as $key) {
            setting()->set('Azienda.' . $key, $post[$key] ?? null);
        }

        return redirect()->to('impostazioni/parametri')->with('success', 'Impostazioni salvate.');
    }

    /**
     * Carica o sostituisce il logo aziendale, indipendentemente dagli altri parametri.
     *
     * La destinazione e' public/uploads/, servita direttamente dal web server: il logo
     * deve essere raggiungibile via URL (layout e stampe PDF), quindi non puo' stare in
     * WRITEPATH come il CSV dell'import. Tutta la difesa sta percio' nella validazione e
     * nel nome del file, mai nell'estensione dichiarata dal client.
     *
     * L'SVG non e' ammesso di proposito: e' un documento XML che puo' contenere <script>,
     * e servito dallo stesso dominio diventa XSS persistente per chi apre il file diretto.
     * Nota che is_image da solo non lo escluderebbe, perche' image/svg+xml inizia per
     * "image/" — serve mime_in. Vedi punto 2 di docs/review/2026-08-16-review-progetto.md.
     */
    public function cambiaLogo()
    {
        $regole = [
            'sede_logo' => [
                'rules'  => 'uploaded[sede_logo]|is_image[sede_logo]|mime_in[sede_logo,image/png,image/jpeg,image/webp]|max_size[sede_logo,1024]',
                'errors' => [
                    'uploaded' => 'Selezionare un file da caricare.',
                    'is_image' => 'Il file caricato non e\' un\'immagine.',
                    'mime_in'  => 'Sono ammessi solo file PNG, JPG o WEBP.',
                    'max_size' => 'Il logo supera 1 MB.',
                ],
            ],
        ];

        if (! $this->validate($regole)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $logo = $this->request->getFile('sede_logo');

        $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Estensione dal MIME rilevato dal server ispezionando il contenuto, non da
        // getClientExtension() che riporta il nome scelto dal browser (cioe' dall'utente).
        // mime_in garantisce gia' che la chiave esista.
        $estensioni = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $filename   = 'logo_azienda.' . $estensioni[$logo->getMimeType()];

        $precedente = setting('Azienda.sede_logo_path');
        $logo->move($dir, $filename, true);
        setting()->set('Azienda.sede_logo_path', 'uploads/' . $filename);

        // Cambiando formato il file vecchio resterebbe orfano e comunque servito dal web
        if ($precedente && $precedente !== 'uploads/' . $filename && is_file(FCPATH . $precedente)) {
            unlink(FCPATH . $precedente);
        }

        return redirect()->to('impostazioni/parametri')->with('success', 'Logo aggiornato.');
    }
}
