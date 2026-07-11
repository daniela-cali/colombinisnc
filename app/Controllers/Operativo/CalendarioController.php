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

        $tipiPerId = [];
        foreach ((new TipiInterventoModel())->attivi() as $t) {
            $tipiPerId[(int) $t['id']] = $t;
        }

        $pool = (new InterventiModel())->poolDaPianificare();

        $materialiPerIntervento = [];
        foreach ((new InterventiMaterialiModel())->daPortarePerInterventi(array_column($pool, 'id')) as $m) {
            $materialiPerIntervento[(int) $m['intervento_id']][] = $m;
        }

        // Ordinamento PHP: urgenza desc, poi data di inserimento asc (i più vecchi prima)
        usort($pool, function ($a, $b) {
            if ((int) $a['urgenza'] !== (int) $b['urgenza']) {
                return (int) $b['urgenza'] - (int) $a['urgenza'];
            }
            return strcmp($a['created_at'], $b['created_at']);
        });

        $zoneLabel = ClientiModel::ZONE_LABEL + ['nessuna' => 'Senza zona'];

        $poolPerZona = [];
        foreach ($pool as $i) {
            $zona = ($i['cliente_zona'] !== null && $i['cliente_zona'] !== '')
                ? (int) $i['cliente_zona']
                : 'nessuna';
            $poolPerZona[$zona][] = $i;
        }
        $zonaOrdini = [-1 => 0, 0 => 1, 1 => 2, 'nessuna' => 3];
        uksort($poolPerZona, fn($a, $b) => ($zonaOrdini[$a] ?? 99) <=> ($zonaOrdini[$b] ?? 99));

        $scadenze = (new InterventiModel())->scadenzeAperte();

        // Data su cui aprire il calendario (es. click su un avviso in campanella):
        // riformattata per passare al JS solo una data pulita, mai input grezzo.
        $data         = $this->request->getGet('data');
        $dataIniziale = ($data && strtotime($data)) ? date('Y-m-d', strtotime($data)) : null;

        // Assenze future raggruppate per dipendente: usate in JS per l'avviso nel modal Pianifica.
        $assenzePerDipendente = [];
        foreach ((new AssenzeModel())->daOggiInPoi() as $a) {
            $assenzePerDipendente[(int) $a['personale_id']][] = [
                'data_inizio' => $a['data_inizio'],
                'data_fine'   => $a['data_fine'],
                'tipo_label'  => AssenzeModel::TIPI_LABEL[$a['tipo']] ?? ucfirst($a['tipo']),
            ];
        }

        return view('operativo/calendario/index', [
            'title'      => 'Calendario',
            'page_title' => 'Calendario Interventi',
            'tecnici'    => $tecnici,
            'tipiPerId'  => $tipiPerId,
            'pool'       => $pool,
            'materialiPerIntervento' => $materialiPerIntervento,
            'poolPerZona'=> $poolPerZona,
            'zoneLabel'  => $zoneLabel,
            'scadenze'   => $scadenze,
            'oraInizio'  => '08:00',
            'help_sezione' => 'calendario',
            'puoPromemoria' => auth()->user()->inGroup('ufficio', 'admin', 'developer'),
            'dataIniziale'  => $dataIniziale,
            'assenzePerDipendente' => $assenzePerDipendente,
        ]);
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
                    'icona'        => $i['tipo_icona'] ?: 'bi-tools',
                    'stato'        => $i['stato'],
                    'descrizione'  => $i['descrizione'] ?: '',
                    'citta'        => $i['cliente_citta'] ?: '',
                    'data_scadenza'=> $i['data_scadenza'] ?? null,
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
     * Aggiorna data_pianificata dopo un drag-and-drop di un evento già pianificato.
     */
    public function sposta()
    {
        $id    = (int) $this->request->getPost('id');
        $start = $this->request->getPost('start');

        if (! $id || ! $start) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'msg' => 'Dati mancanti']);
        }

        (new InterventiModel())->update($id, [
            'data_pianificata' => date('Y-m-d H:i:s', strtotime($start)),
        ]);

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
