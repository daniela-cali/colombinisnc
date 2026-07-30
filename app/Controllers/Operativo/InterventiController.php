<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\ArticoliModel;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiNoteModel;
use App\Models\PersonaleModel;
use App\Models\TipiInterventoModel;

class InterventiController extends BaseController
{
    /**
     * Lista interventi della sezione richiesta (?sezione=generale|piscine|addolcitori).
     * La sezione corrisponde alla categoria del tipo intervento; se assente o non valida
     * ricade su "generale" (che include anche gli interventi senza tipo). La validazione
     * interroga CATEGORIE_LABEL, così aggiungere una categoria non richiede modifiche qui.
     */
    public function index(): string
    {
        $categorie = TipiInterventoModel::CATEGORIE_LABEL;
        $sezione   = $this->request->getGet('sezione');

        if (! array_key_exists($sezione, $categorie)) {
            $sezione = TipiInterventoModel::CATEGORIA_GENERALE;
        }

        return view('operativo/interventi/index', [
            'interventi'    => (new InterventiModel())->elencoCompleto($sezione),
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'    => InterventiModel::STATI_LABEL,
            'sezione'       => $sezione,
            'sezioneLabel'  => $categorie[$sezione],
            'help_sezione'  => 'interventi',
        ]);
    }

    /**
     * Form nuovo intervento.
     * Se arriva ?cliente_id=X dalla scheda cliente, pre-compila il select cliente.
     * Se arriva ?extra=1&abbonamento_id=X, carica l'abbonamento per pre-compilare
     * cliente e tipo intervento in readonly (visita extra da abbonamento).
     */
    public function nuovo(): string
    {
        $abbonamentoId    = (int) $this->request->getGet('abbonamento_id');
        $extra            = (int) $this->request->getGet('extra');
        $clienteId        = (int) $this->request->getGet('cliente_id');
        $cantiereId       = (int) $this->request->getGet('cantiere_id');
        $tipoInterventoId    = 0;
        $haPuliziaFondo      = false;
        $cantiere            = null;
        $descrizioneDefault  = '';

        if ($extra && $abbonamentoId) {
            $abbonamento = (new \App\Models\AbbonamentiModel())->find($abbonamentoId);
            if ($abbonamento) {
                $clienteId        = (int) $abbonamento['cliente_id'];
                $tipoInterventoId = (int) $abbonamento['tipo_intervento_id'];
                $tipo             = (new TipiInterventoModel())->find($tipoInterventoId);
                $haPuliziaFondo   = ! empty($tipo['ha_pulizia_fondo']);

                // Precompilata qui perché per le visite extra il select tipo_intervento_id è
                // disabled: l'auto-precompilazione JS legata al suo evento "change" non scatta mai.
                $cliente            = (new ClientiModel())->find($clienteId);
                $descrizioneDefault = ($cliente ? ClientiModel::denominazione($cliente) : '')
                    . ': visita extra ' . ($tipo['nome'] ?? '');
            }
        }

        // Intervento aperto dalla scheda cantiere: pre-compila e blocca il cliente sul
        // cliente del cantiere, e mostra il cantiere in sola lettura nel form.
        if ($cantiereId) {
            $cantiere = (new \App\Models\CantieriModel())->find($cantiereId);
            if ($cantiere) {
                $clienteId        = (int) $cantiere['cliente_id'];
                $tipoInterventoId = (int) ($cantiere['tipo_intervento_id'] ?? 0);
            } else {
                $cantiereId = 0;
            }
        }

        return view('operativo/interventi/nuovo', [
            'clienti'              => (new ClientiModel())->elencoCompleto(),
            'tecnici'              => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
            'tipi'                 => (new TipiInterventoModel())->attivi(),
            'prioritaLabel'        => InterventiModel::PRIORITA_LABEL,
            'statiLabel'           => InterventiModel::STATI_LABEL,
            'articoliPerCat'       => (new ArticoliModel())->perCategoria(),
            'cliente_id'           => $clienteId,
            'abbonamento_id'       => $abbonamentoId,
            'extra'                => $extra,
            'tipo_intervento_id'   => $tipoInterventoId,
            'ha_pulizia_fondo'     => $haPuliziaFondo,
            'cantiere_id'          => $cantiereId,
            'cantiere'             => $cantiere,
            'descrizioneDefault'   => $descrizioneDefault,
            'from'                 => $this->request->getGet('from') ?? '',
            'assenzePerDipendente' => (new \App\Models\AssenzeModel())->mappaPerDipendente(),
        ]);
    }

    /**
     * Salva il nuovo intervento.
     */
    public function store()
    {
        $model = new InterventiModel();

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $erroreAssenza = $this->erroreAssenzaTecnico(
            (int) $this->request->getPost('tecnico_id'),
            $this->request->getPost('data_pianificata')
        );
        if ($erroreAssenza) {
            return redirect()->back()->withInput()->with('errors', ['tecnico_id' => $erroreAssenza]);
        }

        $fase   = $this->request->getPost('fase');
        $id     = $model->insert(array_merge($this->request->getPost(), [
            'urgenza'  => (int) (bool) $this->request->getPost('urgenza'),
            'apertura' => $fase === 'apertura' ? 1 : 0,
            'chiusura' => $fase === 'chiusura' ? 1 : 0,
        ]));
        $codice = $model->find($id)['codice'];

        $matModel      = new InterventiMaterialiModel();
        $materialiPost = $this->request->getPost('materiali') ?? [];
        foreach ($materialiPost as $m) {
            if (empty($m['articolo_id']) && empty($m['descrizione'])) {
                continue;
            }
            $matModel->insert(array_merge($m, [
                'intervento_id' => $id,
                'cliente_id'    => $this->request->getPost('cliente_id'),
            ]));
        }

        // Collega i materiali sospesi selezionati dal dispatcher al nuovo intervento.
        $sospesiIds = array_filter(array_map('intval', $this->request->getPost('sospesi_ids') ?? []));
        foreach ($sospesiIds as $sospId) {
            $matModel->update($sospId, ['intervento_id' => $id]);
        }

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'operativo/interventi';

        return redirect()->to($dest)->with('success', 'Intervento ' . $codice . ' creato.');
    }

    /**
     * Scheda read-only di un intervento.
     */
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $intervento = (new InterventiModel())->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        $cliente  = (new ClientiModel())->find($intervento['cliente_id']);
        $tecnico  = $intervento['tecnico_id'] ? (new PersonaleModel())->find($intervento['tecnico_id']) : null;
        $tipo     = $intervento['tipo_intervento_id'] ? (new TipiInterventoModel())->find($intervento['tipo_intervento_id']) : null;
        $cantiere = $intervento['cantiere_id'] ? (new \App\Models\CantieriModel())->find($intervento['cantiere_id']) : null;

        // Esclude i materiali appena tornati sospesi da questa stessa chiusura (tag "[Da <codice>]"):
        // altrimenti il tecnico se li ritroverebbe subito nel modal "prossima visita" appena dopo averli smarcati.
        $tagQuestoIntervento = '[Da ' . $intervento['codice'] . ']';
        $materialiSospesi = array_values(array_filter(
            (new InterventiMaterialiModel())->sospesiPerCliente($intervento['cliente_id']),
            fn ($m) => ! str_starts_with((string) $m['note'], $tagQuestoIntervento)
        ));

        return view('operativo/interventi/show', [
            'intervento'   => $intervento,
            'cliente'      => $cliente,
            'tecnico'      => $tecnico,
            'tipo'         => $tipo,
            'cantiere'     => $cantiere,
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'   => InterventiModel::STATI_LABEL,
            'materiali'    => (new InterventiMaterialiModel())->perIntervento($id),
            'note'         => (new InterventiNoteModel())->perIntervento($id),
            'articoliPerCat' => (new ArticoliModel())->perCategoria(),
            'materialiSospesi' => $materialiSospesi,
            'mostraStepMateriali' => (bool) session()->getFlashdata('mostra_step_materiali'),
        ]);
    }

    /**
     * Chiude l'intervento (stato → completato).
     * `consegnato[]` contiene gli ID dei materiali da_portare marcati come consegnati nella
     * checklist; la differenza con l'insieme completo torna sospesa sul cliente. Imposta sempre
     * la flashdata mostra_step_materiali, che fa aprire in automatico il modal "prossima visita".
     */
    public function chiudi(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        // Guardia di sicurezza: la UI nasconde il pulsante in questi stati,
        // ma un POST diretto all'URL bypasserebbe il controllo visivo.
        if (in_array($intervento['stato'], [InterventiModel::STATO_COMPLETATO, InterventiModel::STATO_ANNULLATO])) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'L\'intervento è già chiuso o annullato.');
        }

        $model->update($id, [
            'stato'              => InterventiModel::STATO_COMPLETATO,
            'pulizia_fondo'      => (int) (bool) $this->request->getPost('pulizia_fondo'),
            'data_completamento' => date('Y-m-d H:i:s'),
        ]);

        $matModel     = new InterventiMaterialiModel();
        $idsDaPortare = $matModel->idsDaPortarePerIntervento($id);

        if (! empty($idsDaPortare)) {
            $consegnatiPost = array_map('intval', $this->request->getPost('consegnato') ?? []);
            $consegnati     = array_intersect($idsDaPortare, $consegnatiPost);
            $nonConsegnati  = array_diff($idsDaPortare, $consegnatiPost);

            $matModel->consegnaSelezionati($consegnati, $id);

            if (! empty($nonConsegnati)) {
                // Materiali non portati: tornano tra i sospesi del cliente
                // con una nota che ricorda da quale intervento provenivano.
                $matModel->liberaSelezionati($nonConsegnati, $id, $intervento['codice']);

                // Solo per interventi da abbonamento: provo a riassegnare i materiali al prossimo intervento.
                if (! empty($intervento['abbonamento_id'])) {
                    // max 2 per rilevare ambiguità: se ne tornano 2 con stessa scadenza, fallback manuale
                    $prossimi = $model->prossimiPerAbbonamento(
                        (int) $intervento['abbonamento_id'],
                        $intervento['data_scadenza']
                    );

                    if (count($prossimi) === 1) {
                        $matModel->assegnaAdIntervento($nonConsegnati, $prossimi[0]['id']);
                    }
                    // Se 0 risultati o 2+ (ambiguità): materiali già sospesi sul cliente, gestione manuale.
                }
            }
        }

        return redirect()->to('operativo/interventi/' . $id)
            ->with('success', 'Intervento ' . esc($intervento['codice']) . ' chiuso.')
            ->with('mostra_step_materiali', true);
    }

    /**
     * Form modifica intervento.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $intervento = (new InterventiModel())->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if ($intervento['stato'] === InterventiModel::STATO_ANNULLATO) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'Un intervento annullato non può essere modificato.');
        }

        $cliente  = (new ClientiModel())->find($intervento['cliente_id']);
        $cantiere = $intervento['cantiere_id'] ? (new \App\Models\CantieriModel())->find($intervento['cantiere_id']) : null;

        return view('operativo/interventi/edit', [
            'intervento'    => $intervento,
            'cliente'       => $cliente,
            'cantiere'      => $cantiere,
            'tecnici'       => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
            'tipi'          => (new TipiInterventoModel())->attivi(),
            'prioritaLabel' => InterventiModel::PRIORITA_LABEL,
            'statiLabel'    => InterventiModel::STATI_LABEL,
            'materiali'     => (new InterventiMaterialiModel())->perIntervento($id),
            'note'          => (new InterventiNoteModel())->perIntervento($id),
            'articoliPerCat' => (new ArticoliModel())->perCategoria(),
            'from'          => $this->request->getGet('from') ?? '',
            'assenzePerDipendente' => (new \App\Models\AssenzeModel())->mappaPerDipendente(),
        ]);
    }

    /**
     * Aggiorna l'intervento.
     */
    public function update(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Blocca solo se il tecnico o la data cambiano davvero rispetto al record esistente:
        // un conflitto già presente (es. assenza aggiunta dopo la pianificazione) non deve
        // impedire di salvare altre modifiche non collegate.
        $tecnicoIdPost = (int) $this->request->getPost('tecnico_id');
        $dataPianPost  = $this->request->getPost('data_pianificata');
        $dataPianPostNorm = $dataPianPost ? date('Y-m-d H:i:s', strtotime($dataPianPost)) : null;
        $cambiaAssegnazione = $tecnicoIdPost !== (int) $intervento['tecnico_id']
            || $dataPianPostNorm !== $intervento['data_pianificata'];

        if ($cambiaAssegnazione) {
            $erroreAssenza = $this->erroreAssenzaTecnico($tecnicoIdPost, $dataPianPost);
            if ($erroreAssenza) {
                return redirect()->back()->withInput()->with('errors', ['tecnico_id' => $erroreAssenza]);
            }
        }

        $fase = $this->request->getPost('fase');
        $model->update($id, array_merge($this->request->getPost(), [
            'urgenza'  => (int) (bool) $this->request->getPost('urgenza'),
            'apertura' => $fase === 'apertura' ? 1 : 0,
            'chiusura' => $fase === 'chiusura' ? 1 : 0,
        ]));

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'operativo/interventi/' . $id . '/edit';

        return redirect()->to($dest)->with('success', 'Intervento aggiornato.');
    }

    /**
     * Segna l'inizio lavoro: stato → in corso, solo da "pianificato".
     * `data_inizio_lavoro` è puramente informativa (calcolo futuro della durata media
     * degli interventi, mobile_ux_spec.md §2.5): nessuna funzionalità dipende da questo stato oggi.
     */
    public function inizia(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if ($intervento['stato'] !== InterventiModel::STATO_PIANIFICATO) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'L\'intervento può essere avviato solo dallo stato "Pianificato".');
        }

        $model->update($id, [
            'stato'              => InterventiModel::STATO_IN_CORSO,
            'data_inizio_lavoro' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('operativo/interventi/' . $id)
            ->with('success', 'Intervento ' . esc($intervento['codice']) . ' avviato.');
    }

    /**
     * Annulla l'intervento: stato → annullato e libera i materiali non consegnati verso i sospesi.
     * Unico punto in cui lo stato "annullato" viene impostato: non è più selezionabile dal form di modifica.
     */
    public function annulla(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if (in_array($intervento['stato'], [InterventiModel::STATO_COMPLETATO, InterventiModel::STATO_ANNULLATO])) {
            return redirect()->to('operativo/interventi/' . $id)
                ->with('error', 'L\'intervento è già chiuso o annullato.');
        }

        $model->update($id, ['stato' => InterventiModel::STATO_ANNULLATO]);
        (new InterventiMaterialiModel())->liberaPerIntervento($id, $intervento['codice']);

        return redirect()->to('operativo/interventi/' . $id)
            ->with('success', 'Intervento ' . esc($intervento['codice']) . ' annullato.');
    }

    /**
     * Elimina l'intervento (hard delete).
     * Consentito solo se lo stato è "annullato" — impedisce la cancellazione di interventi attivi.
     * Guard difensivo: libera i materiali prima del CASCADE per coprire record storici impostati
     * ad "annullato" prima dell'introduzione del bottone dedicato (quando era possibile farlo
     * dal dropdown del form). Sul DB di produzione (svuotato al go-live) questo non servirà.
     */
    public function delete(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return redirect()->to('operativo/interventi')->with('error', 'Intervento non trovato.');
        }

        if ($intervento['stato'] !== InterventiModel::STATO_ANNULLATO) {
            return redirect()->to('operativo/interventi/' . $id . '/edit')
                ->with('error', 'L\'intervento può essere eliminato solo se è nello stato "Annullato".');
        }

        $codice    = $intervento['codice'];
        $clienteId = $intervento['cliente_id'];

        // Libera i materiali prima del CASCADE: tornano sospesi con nota dell'intervento di origine.
        (new InterventiMaterialiModel())->liberaPerIntervento($id, $codice);
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'anagrafiche/clienti/' . $clienteId;

        return redirect()->to($dest)->with('success', 'Intervento ' . esc($codice) . ' eliminato.');
    }

    /**
     * Pianifica l'intervento: imposta data_pianificata, tecnico_id facoltativo e stato → pianificato.
     * Chiamato via AJAX dal drag-and-drop sul calendario.
     */
    public function pianifica(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento || $intervento['stato'] !== InterventiModel::STATO_DA_PIANIFICARE) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Intervento non pianificabile.']);
        }

        $dataPian = $this->request->getPost('data_pianificata');
        if (! $dataPian) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Data mancante.']);
        }

        $tecnicoId = (int) $this->request->getPost('tecnico_id');

        $erroreAssenza = $this->erroreAssenzaTecnico($tecnicoId, $dataPian);
        if ($erroreAssenza) {
            return $this->response->setJSON(['ok' => false, 'msg' => $erroreAssenza]);
        }

        $dati = [
            'data_pianificata' => date('Y-m-d H:i:s', strtotime($dataPian)),
            'stato'            => InterventiModel::STATO_PIANIFICATO,
        ];
        if ($tecnicoId) {
            $dati['tecnico_id'] = $tecnicoId;
        }

        $model->update($id, $dati);

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    /**
     * Rimuove la pianificazione: azzera data_pianificata e tecnico_id, stato → da_pianificare.
     * Chiamato via AJAX dal bottone × sugli eventi del calendario.
     */
    public function annullaPianificazione(int $id)
    {
        $model      = new InterventiModel();
        $intervento = $model->find($id);

        if (! $intervento) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Intervento non trovato.']);
        }

        if (! in_array($intervento['stato'], [InterventiModel::STATO_PIANIFICATO, InterventiModel::STATO_IN_CORSO])) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Impossibile rimuovere la pianificazione in questo stato.']);
        }

        $model->update($id, [
            'data_pianificata' => null,
            'tecnico_id'       => null,
            'stato'            => InterventiModel::STATO_DA_PIANIFICARE,
        ]);

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    /**
     * Aggiunge una nota al diario di un intervento.
     * data_nota è facoltativa: se vuota il model usa la data odierna.
     */
    public function aggiungiNota()
    {
        if (! $this->validate([
            'intervento_id' => 'required|is_natural_no_zero',
            'data_nota'     => 'permit_empty|valid_date[Y-m-d]',
            'testo'         => 'required|min_length[2]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $interventoId = (int) $this->request->getPost('intervento_id');
        (new InterventiNoteModel())->insert($this->request->getPost());

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'operativo/interventi/' . $interventoId . '/edit#sec-diario';

        return redirect()->to($dest)->with('success', 'Nota aggiunta al diario.');
    }

    /**
     * Elimina una nota dal diario, tornando alla scheda dell'intervento di origine.
     */
    public function eliminaNota(int $id)
    {
        $model = new InterventiNoteModel();
        $nota  = $model->find($id);

        if (! $nota) {
            return redirect()->back()->with('error', 'Nota non trovata.');
        }

        $interventoId = (int) $nota['intervento_id'];
        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url()))
            ? $from
            : 'operativo/interventi/' . $interventoId . '/edit#sec-diario';

        return redirect()->to($dest)->with('success', 'Nota eliminata.');
    }

    // -------------------------------------------------------------------------

    /**
     * Se il tecnico indicato risulta assente nella data di pianificazione indicata, restituisce
     * il messaggio da mostrare in form; altrimenti null. Nessun controllo se manca il tecnico
     * o la data (intervento non ancora assegnato/pianificato: nessun conflitto possibile).
     */
    private function erroreAssenzaTecnico(int $tecnicoId, ?string $dataPianificata): ?string
    {
        if (! $tecnicoId || ! $dataPianificata) {
            return null;
        }

        $assenza = (new \App\Models\AssenzeModel())->copreData($tecnicoId, date('Y-m-d H:i:s', strtotime($dataPianificata)));
        if (! $assenza) {
            return null;
        }

        $tecnico = (new PersonaleModel())->find($tecnicoId);
        $nome    = $tecnico ? trim($tecnico['cognome'] . ' ' . $tecnico['nome']) : 'Il tecnico selezionato';

        return $nome . ' risulta assente (' . (\App\Models\AssenzeModel::TIPI_LABEL[$assenza['tipo']] ?? $assenza['tipo']) . ') in questa data.';
    }

    /**
     * Regole di validazione comuni per insert e update.
     * I valori ammessi per tipo e stato vengono letti dalle costanti del model:
     * aggiungere un valore richiede solo modificare InterventiModel.
     */
    private function regolaValidazione(): array
    {
        $prioritaAmmesse = implode(',', array_keys(InterventiModel::PRIORITA_LABEL));
        $statiAmmessi    = implode(',', array_keys(InterventiModel::STATI_LABEL));

        return [
            'cliente_id'         => 'required|is_natural_no_zero',
            'descrizione'        => 'required|min_length[3]',
            'priorita'           => 'required|in_list[' . $prioritaAmmesse . ']',
            'stato'              => 'required|in_list[' . $statiAmmessi . ']',
            'tipo_intervento_id' => 'required|is_natural_no_zero',
            'data_pianificata'   => 'permit_empty|valid_date[Y-m-d\TH:i]',
            'data_scadenza'      => 'permit_empty|valid_date[Y-m-d]',
            'durata_stimata'     => 'permit_empty|is_natural',
        ];
    }
}
