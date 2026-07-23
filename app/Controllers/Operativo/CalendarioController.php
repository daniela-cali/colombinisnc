<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\AssenzeModel;
use App\Models\ClientiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use App\Models\PromemoriaModel;
use App\Models\TipiInterventoModel;

class CalendarioController extends BaseController
{
    /**
     * Vista principale con pool degli interventi da pianificare e calendario.
     */
    public function index(): string
    {
        helper('colore');

        $tecnici = (new PersonaleModel())->elencoPerGruppi(['tecnico']);

        // Fine del periodo iniziale mostrato dal pool: fine della settimana ISO corrente.
        // La vista Giorno (mobile) è comunque contenuta nella settimana, quindi vale per entrambe.
        $oggi        = new \DateTime();
        $isoDay      = (int) $oggi->format('N'); // 1 (lun) … 7 (dom)
        $finePeriodo = (clone $oggi)->modify('+' . (7 - $isoDay) . ' days')->format('Y-m-d');

        $datiPool = $this->datiPool($finePeriodo);
        $tipiPerId              = $datiPool['tipiPerId'];
        $materialiPerIntervento = $datiPool['materialiPerIntervento'];
        $zoneLabel              = $datiPool['zoneLabel'];
        $poolPerZona            = $datiPool['poolPerZona'];
        $totaliPerZona          = $datiPool['totaliPerZona'];

        // Raggruppate per motivo (mancato/ritardo/fermo) per la barra a pill collassabili:
        // il foreach smista senza riordinare, l'ordine (già corretto) viene dal model.
        $scadenzePerMotivo = ['mancato' => [], 'ritardo' => [], 'fermo' => []];
        foreach ((new InterventiModel())->scadenzeInRitardo() as $s) {
            $scadenzePerMotivo[$s['motivo']][] = $s;
        }

        // Data su cui aprire il calendario (es. click su un avviso in campanella):
        // riformattata per passare al JS solo una data pulita, mai input grezzo.
        $data         = $this->request->getGet('data');
        $dataIniziale = ($data && strtotime($data)) ? date('Y-m-d', strtotime($data)) : null;

        // Assenze future raggruppate per dipendente: usate in JS per l'avviso nel modal Pianifica.
        $assenzePerDipendente = (new AssenzeModel())->mappaPerDipendente();

        return view('operativo/calendario/index', [
            'title'      => 'Calendario',
            'page_title' => 'Calendario Interventi',
            'tecnici'    => $tecnici,
            'tipiPerId'  => $tipiPerId,
            'totaliPerZona' => $totaliPerZona,
            'totaleDaPianificare' => array_sum($totaliPerZona),
            'materialiPerIntervento' => $materialiPerIntervento,
            'poolPerZona' => $poolPerZona,
            'zoneLabel'  => $zoneLabel,
            'scadenzePerMotivo' => $scadenzePerMotivo,
            'oraInizio'  => setting('Azienda.orario_inizio') ?? '08:00',
            'help_sezione' => 'calendario',
            'puoPromemoria' => auth()->user()->inGroup('ufficio', 'admin', 'developer'),
            'dataIniziale'  => $dataIniziale,
            'assenzePerDipendente' => $assenzePerDipendente,
        ]);
    }

    /**
     * Dati per il pool "da pianificare" filtrati su un periodo: interventi raggruppati per
     * zona/sottogruppo (generico/cantiere/abbonamento) con totali, tipi e materiali per card.
     * Condiviso tra il caricamento iniziale (index()) e il refresh AJAX (poolPeriodo()), così
     * la logica di raggruppamento resta scritta in un solo posto.
     */
    private function datiPool(string $finePeriodo): array
    {
        $tipiPerId = [];
        foreach ((new TipiInterventoModel())->attivi() as $t) {
            $tipiPerId[(int) $t['id']] = $t;
        }

        $pool = (new InterventiModel())->poolDaPianificare($finePeriodo);

        $materialiPerIntervento = [];
        foreach ((new InterventiMaterialiModel())->daPortarePerInterventi(array_column($pool, 'id')) as $m) {
            $materialiPerIntervento[(int) $m['intervento_id']][] = $m;
        }

        $zoneLabel = ClientiModel::ZONE_LABEL + ['nessuna' => 'Senza zona'];

        $poolPerZona = [];
        foreach ($pool as $i) {
            $zona = ($i['cliente_zona'] !== null && $i['cliente_zona'] !== '')
                ? (int) $i['cliente_zona'] //vardump definisce -1 come int, cast di sicurezza contro eventuali stringhe arrivate da db
                : 'nessuna';
            if (! empty($i['cantiere_id'])) {
                $sottogruppo = 'cantiere';
            } elseif (! empty($i['abbonamento_id'])) {
                $sottogruppo = 'abbonamento';
            } else {
                $sottogruppo = 'generico';
            }
            $poolPerZona[$zona][$sottogruppo][] = $i;
        }
        $zonaOrdini = [-1 => 0, 0 => 1, 1 => 2, 'nessuna' => 3];
        uksort($poolPerZona, fn($a, $b) => ($zonaOrdini[$a] ?? 99) <=> ($zonaOrdini[$b] ?? 99));
        $sottogruppoInfo = [
            'generico'    => ['label' => 'Generici',    'icona' => 'bi-tools'],
            'cantiere'    => ['label' => 'Cantieri',    'icona' => 'bi-bricks'],
            'abbonamento' => ['label' => 'Abbonamenti', 'icona' => 'bi-arrow-repeat'],
        ];
        $totaliPerZona = [];
        foreach ($poolPerZona as $zona => $sottogruppo) {
            $totaliPerZona[$zona] = 0;   // <-- inizializza la chiave PRIMA di sommarci sopra
            foreach ($sottogruppoInfo as $definizione => $info) {
                $totaliPerZona[$zona] += count($sottogruppo[$definizione] ?? []);
            }

            $blocchi = [];
            foreach ($sottogruppoInfo as $key => $info) {
                if (empty($sottogruppo[$key])) {
                    continue;
                }
                $blocchi[] = [
                    'key'        => $key,
                    'label'      => $info['label'],
                    'icona'      => $info['icona'],
                    'interventi' => $sottogruppo[$key],
                ];
            }
            $poolPerZona[$zona] = $blocchi;
        }

        return [
            'tipiPerId'              => $tipiPerId,
            'materialiPerIntervento' => $materialiPerIntervento,
            'zoneLabel'              => $zoneLabel,
            'poolPerZona'            => $poolPerZona,
            'totaliPerZona'          => $totaliPerZona,
        ];
    }

    /**
     * Endpoint AJAX: rigenera il pool per il periodo attualmente visibile sul calendario.
     * Riceve `fine` (data ISO, upper bound di data_scadenza) via query string — richiamato
     * dal callback `datesSet` di FullCalendar ogni volta che l'utente cambia periodo/vista.
     */
    public function poolPeriodo(): string
    {
        $finePeriodo = $this->request->getGet('fine') ?? date('Y-m-d');
        $datiPool    = $this->datiPool($finePeriodo);

        return view('operativo/calendario/_pool', array_merge($datiPool, [
            'totaleDaPianificare' => array_sum($datiPool['totaliPerZona']),
        ]));
    }

    /**
     * Restituisce gli eventi in formato FullCalendar JSON per il range richiesto.
     * Accetta ?start=YYYY-MM-DD&end=YYYY-MM-DD&tecnico_id=N
     */
    public function eventi()
    {
        helper('colore');

        $start     = $this->request->getGet('start') ?? date('Y-m-01');
        $end       = $this->request->getGet('end')   ?? date('Y-m-t');
        $tecnicoId = (int) ($this->request->getGet('tecnico_id') ?? 0);

        $interventiEventi = (new InterventiModel())->eventiCalendario($start, $end, $tecnicoId ?: null);

        $materialiPerIntervento = [];
        foreach ((new InterventiMaterialiModel())->daPortarePerInterventi(array_column($interventiEventi, 'id')) as $m) {
            $materialiPerIntervento[(int) $m['intervento_id']][] = [
                'desc' => $m['desc_materiale'],
                'qta'  => $m['quantita'],
                'note' => $m['note'],
            ];
        }

        $events = [];
        foreach ($interventiEventi as $i) {
            $durata  = max(60, (int) ($i['durata_stimata'] ?: $i['tipo_durata'] ?: 60));
            $colore  = $i['tecnico_colore'] ?: '#6c757d';
            $tecnico = $i['tecnico_nome']
                ? trim($i['tecnico_cognome'] . ' ' . $i['tecnico_nome'])
                : 'Non assegnato';

            $events[] = [
                'id'    => $i['id'],
                'title' => $i['cliente_denominazione'] ?: '—',
                'start' => $i['data_pianificata'],
                'end'   => date('Y-m-d H:i:s', strtotime($i['data_pianificata']) + $durata * 60),
                'color' => $colore,
                'textColor' => colore_testo($colore),
                'url'   => base_url('operativo/interventi/' . $i['id']),
                'extendedProps' => [
                    'tecnico'      => $tecnico,
                    'tipo'         => $i['tipo_nome'] ?: '—',
                    'icona'        => $i['tipo_icona'] ?: 'fa-wrench',
                    'stato'        => $i['stato'],
                    'descrizione'  => $i['descrizione'] ?: '',
                    'citta'        => $i['cliente_citta'] ?: '',
                    'data_scadenza' => $i['data_scadenza'] ?? null,
                    'creato'       => $i['created_at'] ?? null,
                    'materiali'    => $materialiPerIntervento[(int) $i['id']] ?? [],
                ],
            ];
        }

        // Promemoria aziendali: eventi viola, non trascinabili, click → modal (no url).
        foreach ((new PromemoriaModel())->perCalendario($start, $end) as $p) {
            $fine = $p['data_ora_fine'] ?: date('Y-m-d H:i:s', strtotime($p['data_ora_inizio']) + 3600);
            $events[] = [
                'id'       => 'prom-' . $p['id'],
                'title'    => $p['titolo'],
                'start'    => $p['data_ora_inizio'],
                'end'      => $fine,
                'color'    => '#6f42c1',
                'editable' => false,
                'extendedProps' => [
                    'tipo_evento' => 'promemoria',
                    'prom_id'     => (int) $p['id'],
                    'titolo'      => $p['titolo'],
                    'inizio'      => $p['data_ora_inizio'],
                    'fine'        => $p['data_ora_fine'],
                    'note'        => $p['note'] ?? '',
                ],
            ];
        }

        // Assenze personale: eventi all-day arancioni, non trascinabili, non cliccabili.
        foreach ((new AssenzeModel())->perCalendario($start, $end) as $a) {
            $tecnico = trim($a['personale_cognome'] . ' ' . $a['personale_nome']);
            $tipoLabel = AssenzeModel::TIPI_LABEL[$a['tipo']] ?? ucfirst($a['tipo']);

            $events[] = [
                'id'       => 'ass-' . $a['id'],
                'title'    => trim($tecnico . ' — ' . $tipoLabel),
                'start'    => $a['data_inizio'],
                // 'end' è esclusivo negli eventi all-day di FullCalendar: +1 giorno per includere l'ultimo giorno di assenza.
                'end'      => date('Y-m-d', strtotime($a['data_fine'] . ' +1 day')),
                'allDay'   => true,
                'color'    => '#e8590c',
                'editable' => false,
                'extendedProps' => [
                    'tipo_evento' => 'assenza',
                    'tecnico'     => $tecnico,
                    'tipo_label'  => $tipoLabel,
                    'note'        => $a['note'] ?? '',
                ],
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Orario suggerito per pianificare un intervento dal pool: il tecnico lavora gli
     * interventi del giorno in sequenza, quindi si propone l'orario subito dopo la fine
     * del suo ultimo impegno già pianificato in quella data (l'inizio giornata configurato
     * in Impostazioni se non ne ha). Solo un default per il form: l'utente può sempre modificarlo.
     */
    public function orarioSuggerito()
    {
        $tecnicoId = (int) ($this->request->getGet('tecnico_id') ?? 0);
        $data      = $this->request->getGet('data') ?? date('Y-m-d');

        $model      = new InterventiModel();
        $esistenti  = $tecnicoId ? $model->agendaGiornoTecnico($tecnicoId, $data) : [];
        $tSuggerito = strtotime($data . ' ' . (setting('Azienda.orario_inizio') ?? '08:00') . ':00');

        foreach ($esistenti as $i) {
            $durata = $model->durataMinuti(
                $i['durata_stimata'] ? (int) $i['durata_stimata'] : null,
                $i['tipo_durata'] ? (int) $i['tipo_durata'] : null
            );
            $tFine = strtotime($i['data_pianificata']) + $durata * 60;
            if ($tFine > $tSuggerito) {
                $tSuggerito = $tFine;
            }
        }

        return $this->response->setJSON([
            'ora'    => date('H:i', $tSuggerito),
            'n_prev' => count($esistenti),
        ]);
    }

    /**
     * Aggiorna data_pianificata dopo un drag-and-drop di un evento già pianificato.
     */
    public function sposta()
    {
        $id    = (int) $this->request->getPost('id');
        $start = $this->request->getPost('start');

        if (! $id || ! $start) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'msg' => 'Dati mancanti']);
        }

        $model      = new InterventiModel();
        $intervento = $model->find($id);
        $nuovaData  = date('Y-m-d H:i:s', strtotime($start));

        if ($intervento && $intervento['tecnico_id']) {
            $assenza = (new AssenzeModel())->copreData((int) $intervento['tecnico_id'], $nuovaData);
            if ($assenza) {
                $tecnico = (new PersonaleModel())->find($intervento['tecnico_id']);
                $nome    = $tecnico ? trim($tecnico['cognome'] . ' ' . $tecnico['nome']) : 'Il tecnico assegnato';
                $msg     = $nome . ' risulta assente (' . (AssenzeModel::TIPI_LABEL[$assenza['tipo']] ?? $assenza['tipo']) . ') in questa data.';

                return $this->response->setJSON(['ok' => false, 'msg' => $msg]);
            }
        }

        $model->update($id, ['data_pianificata' => $nuovaData]);

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    /**
     * Genera il viaggio della giornata per il tecnico selezionato (funzionalità v0.13.0).
     */
    public function generaViaggio()
    {
        $data = $this->request->getPost('data') ?? date('Y-m-d');
        return redirect()->to('operativo/viaggi?data=' . urlencode($data));
    }
}
