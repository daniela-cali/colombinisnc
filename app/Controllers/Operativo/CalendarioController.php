<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use App\Models\TipiInterventoModel;

class CalendarioController extends BaseController
{
    /**
     * Vista principale con pool degli interventi da pianificare e calendario.
     */
    public function index(): string
    {
        $tecnici = (new PersonaleModel())->elencoPerGruppi(['tecnico']);

        $tipiPerId = [];
        foreach ((new TipiInterventoModel())->attivi() as $t) {
            $tipiPerId[(int) $t['id']] = $t;
        }

        $pool = (new InterventiModel())
            ->select("interventi.id, interventi.tipo_intervento_id, interventi.priorita,
                      interventi.urgenza, interventi.data_scadenza, interventi.durata_stimata,
                      interventi.descrizione,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione,
                      c.citta AS cliente_citta,
                      c.zona AS cliente_zona,
                      c.distanza_sede")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->where('interventi.stato', InterventiModel::STATO_DA_PIANIFICARE)
            ->findAll();

        // Ordinamento PHP: urgenza desc, scadenza asc (null in fondo), distanza asc (null in fondo)
        usort($pool, function ($a, $b) {
            if ((int) $a['urgenza'] !== (int) $b['urgenza']) {
                return (int) $b['urgenza'] - (int) $a['urgenza'];
            }
            $da = $a['data_scadenza'] ?? '9999-12-31';
            $db = $b['data_scadenza'] ?? '9999-12-31';
            if ($da !== $db) {
                return strcmp($da, $db);
            }
            return ((float) ($a['distanza_sede'] ?? 99999)) <=> ((float) ($b['distanza_sede'] ?? 99999));
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

        $scadenze = (new InterventiModel())
            ->select("interventi.id, interventi.data_scadenza,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione")
            ->join('clienti c', 'c.id = interventi.cliente_id', 'left')
            ->where('interventi.data_scadenza IS NOT NULL', null, false)
            ->where('interventi.stato !=', InterventiModel::STATO_COMPLETATO)
            ->where('interventi.stato !=', InterventiModel::STATO_ANNULLATO)
            ->orderBy('interventi.data_scadenza', 'ASC')
            ->findAll();

        return view('operativo/calendario/index', [
            'title'      => 'Calendario',
            'page_title' => 'Calendario Interventi',
            'tecnici'    => $tecnici,
            'tipiPerId'  => $tipiPerId,
            'pool'       => $pool,
            'poolPerZona'=> $poolPerZona,
            'zoneLabel'  => $zoneLabel,
            'scadenze'   => $scadenze,
            'oraInizio'  => '08:00',
        ]);
    }

    /**
     * Restituisce gli eventi in formato FullCalendar JSON per il range richiesto.
     * Accetta ?start=YYYY-MM-DD&end=YYYY-MM-DD&tecnico_id=N
     */
    public function eventi()
    {
        $start     = $this->request->getGet('start') ?? date('Y-m-01');
        $end       = $this->request->getGet('end')   ?? date('Y-m-t');
        $tecnicoId = (int) ($this->request->getGet('tecnico_id') ?? 0);

        $q = (new InterventiModel())
            ->select("interventi.id, interventi.stato, interventi.data_pianificata,
                      interventi.durata_stimata, interventi.descrizione, interventi.data_scadenza,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione,
                      c.citta AS cliente_citta,
                      p.nome AS tecnico_nome, p.cognome AS tecnico_cognome, p.colore AS tecnico_colore,
                      ti.durata_default AS tipo_durata, ti.nome AS tipo_nome, ti.icona AS tipo_icona")
            ->join('clienti c',         'c.id  = interventi.cliente_id',        'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.data_pianificata >=', $start)
            ->where('interventi.data_pianificata <',  $end)
            ->where('interventi.data_pianificata IS NOT NULL', null, false)
            ->where('interventi.stato !=', InterventiModel::STATO_ANNULLATO);

        if ($tecnicoId) {
            $q->where('interventi.tecnico_id', $tecnicoId);
        }

        $events = [];
        foreach ($q->findAll() as $i) {
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
                'url'   => base_url('operativo/interventi/' . $i['id']),
                'extendedProps' => [
                    'tecnico'      => $tecnico,
                    'tipo'         => $i['tipo_nome'] ?: '—',
                    'icona'        => $i['tipo_icona'] ?: 'bi-tools',
                    'stato'        => $i['stato'],
                    'descrizione'  => $i['descrizione'] ?: '',
                    'citta'        => $i['cliente_citta'] ?: '',
                    'data_scadenza'=> $i['data_scadenza'] ?? null,
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
        return redirect()->to('operativo/calendario')
            ->with('info', 'Funzionalità disponibile dalla v0.13.0 — Viaggi.');
    }
}
