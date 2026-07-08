<?php

namespace App\Controllers\Anagrafiche;

use App\Controllers\BaseController;
use App\Models\AbbonamentiModel;
use App\Models\AbbonamentiPeriodiModel;
use App\Models\ArticoliModel;
use App\Models\CantieriModel;
use App\Models\CantieriNoteModel;
use App\Models\ClientiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ClientiController extends BaseController
{
    /**
     * Lista clienti con denominazione calcolata e tecnico preferito.
     */
    public function index(): string
    {
        return view('anagrafiche/clienti/index', [
            'clienti'      => (new ClientiModel())->elencoCompleto(),
            'help_sezione' => 'clienti',
        ]);
    }

    /**
     * Scheda cliente in sola lettura: layout verticale scrollabile.
     */
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $matModel = new InterventiMaterialiModel();

        return view('anagrafiche/clienti/show', [
            'cliente'             => $cliente,
            'interventi'          => (new InterventiModel())->perCliente($id),
            'sospesi'             => $matModel->sospesiPerCliente($id),
            'articoliPerCat'      => (new ArticoliModel())->perCategoria(),
            'prioritaLabel'       => InterventiModel::PRIORITA_LABEL,
            'statiLabel'          => InterventiModel::STATI_LABEL,
            'abbonamenti'           => (new AbbonamentiModel())->perCliente($id),
            'abbonamentiLabel'      => AbbonamentiModel::STATI_LABEL,
            'abbonamentiBadge'      => AbbonamentiModel::STATI_BADGE,
            'abbonamentiFrequenze'  => AbbonamentiModel::FREQUENZE_LABEL,
            'cantieri'              => (new CantieriModel())->perCliente($id),
            'cantieriTipiLabel'     => CantieriModel::TIPI_LABEL,
            'cantieriStatiLabel'    => CantieriModel::STATI_LABEL,
            'cantieriStatiBadge'    => CantieriModel::STATI_BADGE,
        ]);
    }

    /**
     * Endpoint AJAX: restituisce i materiali sospesi del cliente come JSON.
     * Usato dal form "Nuovo intervento" per proporre i sospesi alla selezione del cliente.
     */
    public function sospesiJson(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $sospesi = (new InterventiMaterialiModel())->sospesiPerCliente($id);
        return $this->response->setJSON($sospesi);
    }

    /**
     * Storico materiali del cliente: sospesi + per intervento, raggruppati via PHP.
     */
    public function materiali(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $matModel = new InterventiMaterialiModel();

        return view('anagrafiche/clienti/materiali', [
            'cliente'   => $cliente,
            'sospesi'   => $matModel->sospesiPerCliente($id),
            'materiali' => $matModel->perCliente($id),
            'statiLabel' => InterventiModel::STATI_LABEL,
        ]);
    }

    /**
     * Genera il PDF operativo della scheda cliente: anagrafica essenziale,
     * materiali da portare, interventi ancora aperti (da pianificare/pianificati),
     * abbonamento attivo e cantieri aperti/sospesi. Vedi docs/spec/stampa_cliente_pdf_spec.md.
     */
    public function pdf(int $id)
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        // Gli interventi agganciati a un cantiere sono esclusi qui: compaiono già dentro
        // il rispettivo blocco Cantieri del PDF (ultimi 3 interventi, vedi sotto) — altrimenti
        // finirebbero duplicati sia nella lista piatta che nel loro cantiere.
        $interventi = array_values(array_filter(
            (new InterventiModel())->perCliente($id),
            fn ($i) => empty($i['cantiere_id'])
        ));

        // Le visite ricorrenti da abbonamento (priorita = abbonamento) ancora da pianificare
        // sono limitate al mese corrente, altrimenti gli abbonamenti settimanali riempiono
        // il PDF di righe; le richieste manuali (priorita normale/urgente) restano sempre visibili.
        $inizioMese = strtotime('first day of this month');
        $fineMese   = strtotime('last day of this month');

        $daPianificare = array_values(array_filter(
            $interventi,
            function ($i) use ($inizioMese, $fineMese) {
                if ($i['stato'] !== InterventiModel::STATO_DA_PIANIFICARE) {
                    return false;
                }
                if ($i['priorita'] !== InterventiModel::PRIORITA_ABBONAMENTO || empty($i['data_scadenza'])) {
                    return true;
                }
                $scadenza = strtotime($i['data_scadenza']);

                return $scadenza >= $inizioMese && $scadenza <= $fineMese;
            }
        ));
        $pianificati = array_values(array_filter(
            $interventi,
            fn ($i) => in_array($i['stato'], [InterventiModel::STATO_PIANIFICATO, InterventiModel::STATO_IN_CORSO], true)
        ));

        $periodiModel = new AbbonamentiPeriodiModel();

        $abbonamenti = array_values(array_filter(
            (new AbbonamentiModel())->perCliente($id),
            fn ($a) => $a['stato_calcolato'] === AbbonamentiModel::STATO_ATTIVO
        ));
        foreach ($abbonamenti as &$ab) {
            $ab['periodi'] = $periodiModel->perAbbonamento($ab['id']);
        }
        unset($ab);

        $noteModel = new CantieriNoteModel();
        $intModel  = new InterventiModel();

        $cantieri = array_values(array_filter(
            (new CantieriModel())->perCliente($id),
            fn ($c) => in_array($c['stato'], [CantieriModel::STATO_APERTO, CantieriModel::STATO_SOSPESO], true)
        ));
        foreach ($cantieri as &$c) {
            $c['note']       = array_slice($noteModel->perCantiere($c['id']), 0, 3);
            $c['interventi'] = array_slice($intModel->perCantiere($c['id']), 0, 3);
        }
        unset($c);

        $html = view('anagrafiche/clienti/pdf_scheda_cliente', [
            'cliente'              => $cliente,
            'denominazione'        => ClientiModel::denominazione($cliente),
            'sospesi'              => (new InterventiMaterialiModel())->sospesiPerCliente($id),
            'daPianificare'        => $daPianificare,
            'pianificati'          => $pianificati,
            'prioritaLabel'        => InterventiModel::PRIORITA_LABEL,
            'abbonamenti'          => $abbonamenti,
            'abbonamentiFrequenze' => AbbonamentiModel::FREQUENZE_LABEL,
            'cantieri'             => $cantieri,
            'cantieriStatiLabel'   => CantieriModel::STATI_LABEL,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('cliente-' . $cliente['codice'] . '.pdf', ['Attachment' => false]);
        exit;
    }

    /**
     * Form creazione nuovo cliente.
     */
    public function nuovo(): string
    {
        return view('anagrafiche/clienti/nuovo', [
            'tecnici' => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
        ]);
    }

    /**
     * Salva il nuovo cliente.
     * La geocodifica avviene lato JS: lat, lng, geocoded_at e geocodifica_fallita
     * arrivano già compilati dal form.
     */
    public function store()
    {
        $model = new ClientiModel();
        $tipo  = $this->request->getPost('tipo');

        $rules = $this->regolaValidazione($tipo);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->insert(array_merge($this->request->getPost(), [
            'codice' => $model->generaCodice(),
            'attivo' => 1,
        ]));

        return redirect()->to('anagrafiche/clienti')
            ->with('success', esc($this->denominazioneDaPost()) . ' aggiunto/a con successo.');
    }

    /**
     * Form modifica cliente con tab Anagrafica / Interventi / Materiali.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $cliente = (new ClientiModel())->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        return view('anagrafiche/clienti/edit', [
            'cliente' => $cliente,
            'tecnici' => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
        ]);
    }

    /**
     * Aggiorna il cliente.
     * Se l'utente ha rieseguito la geocodifica dalla scheda, lat/lng/geocoded_at
     * arrivano aggiornati nel POST; altrimenti rimangono i valori pre-caricati nel form.
     */
    public function update(int $id)
    {
        $model   = new ClientiModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $tipo  = $this->request->getPost('tipo');
        $rules = $this->regolaValidazione($tipo);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        return redirect()->to('anagrafiche/clienti/' . $id . '/edit')
            ->with('success', 'Cliente aggiornato.');
    }

    /**
     * Elimina il cliente (hard delete).
     * Blocca preventivamente se esistono record collegati in una qualunque tabella con
     * FK RESTRICT su clienti.id (vedi ClientiModel::relazioniBloccanti) — evita che
     * l'utente veda l'eccezione grezza del DB e mostra invece cosa va rimosso prima.
     */
    public function delete(int $id)
    {
        $model   = new ClientiModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $vincoli = $model->relazioniBloccanti($id);

        if (! empty($vincoli)) {
            $motivi = array_map(
                static fn (array $v) => $v['count'] . ' in "' . $v['tabella'] . '"',
                $vincoli
            );

            return redirect()->to('anagrafiche/clienti')
                ->with('error', 'Impossibile eliminare ' . esc(ClientiModel::denominazione($cliente))
                    . ': record collegati — ' . implode(', ', $motivi) . '.');
        }

        $denom = ClientiModel::denominazione($cliente);
        $model->delete($id);

        return redirect()->to('anagrafiche/clienti')
            ->with('success', esc($denom) . ' eliminato/a.');
    }

    /**
     * Salva la posizione del pin impostata manualmente dalla scheda cliente
     * (mappa Leaflet, vedi docs/spec/mappa_cliente_spec.md). Sostituisce
     * un'eventuale geocodifica automatica fallita.
     */
    public function aggiornaPosizione(int $id)
    {
        $model   = new ClientiModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return redirect()->to('anagrafiche/clienti')->with('error', 'Cliente non trovato.');
        }

        $rules = [
            'lat' => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'lng' => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('anagrafiche/clienti/' . $id . '#sec-posizione')
                ->with('error', 'Coordinate non valide.');
        }

        $model->update($id, [
            'lat'                 => $this->request->getPost('lat'),
            'lng'                 => $this->request->getPost('lng'),
            'geocoded_at'         => date('Y-m-d H:i:s'),
            'geocodifica_fallita' => 0,
            // Passata esplicitamente: senza questo campo normalizza() la ricalcolerebbe
            // sempre dalla longitudine, sovrascrivendo una zona assegnata manualmente
            // (vedi ClientiModel::normalizza()).
            'zona'                => $cliente['zona'],
        ]);

        return redirect()->to('anagrafiche/clienti/' . $id . '#sec-posizione')
            ->with('success', 'Posizione aggiornata.');
    }

    // -------------------------------------------------------------------------

    /**
     * Regole di validazione comuni + quelle condizionali per tipo cliente.
     */
    private function regolaValidazione(string $tipo): array
    {
        $rules = [
            'tipo'     => 'required|in_list[societa,persona_fisica]',
            'telefono' => 'permit_empty|max_length[50]',
            'email'    => 'permit_empty|valid_email|max_length[255]',
            'piva'     => 'permit_empty|max_length[15]',
            'cfisc'    => 'permit_empty|max_length[16]',
            'cap'      => 'permit_empty|max_length[10]',
            'zona'     => 'permit_empty|in_list[-1,0,1]',
        ];

        if ($tipo === ClientiModel::TIPO_SOCIETA) {
            $rules['ragsoc'] = 'required|max_length[255]';
        } else {
            $rules['nome']    = 'required|max_length[100]';
            $rules['cognome'] = 'required|max_length[100]';
        }

        return $rules;
    }

    /**
     * Estrae la denominazione leggibile dai dati POST per i messaggi flash.
     */
    private function denominazioneDaPost(): string
    {
        if ($this->request->getPost('tipo') === ClientiModel::TIPO_SOCIETA) {
            return $this->request->getPost('ragsoc') ?? '';
        }

        return trim(
            ($this->request->getPost('cognome') ?? '') . ' ' .
            ($this->request->getPost('nome') ?? '')
        );
    }
}
