<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\InterventiMaterialiModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ViaggioController extends BaseController
{
    /**
     * Vista giornaliera degli interventi pianificati, raggruppati per zona.
     */
    public function index(): string
    {
        $data = $this->dataValida();
        ['perZona' => $perZona, 'zoneLabel' => $zoneLabel, 'totale' => $totale, 'materialiPerIntervento' => $materialiPerIntervento] = $this->fetchGiornata($data);

        return view('operativo/viaggio/index', [
            'title'                  => 'Viaggi',
            'data'                   => $data,
            'dataPrecedente'         => date('Y-m-d', strtotime($data . ' -1 day')),
            'dataSuccessiva'         => date('Y-m-d', strtotime($data . ' +1 day')),
            'perZona'                => $perZona,
            'zoneLabel'              => $zoneLabel,
            'totale'                 => $totale,
            'materialiPerIntervento' => $materialiPerIntervento,
        ]);
    }

    /**
     * Genera il PDF del foglio viaggio per la data indicata.
     */
    public function pdf(): void
    {
        $data = $this->dataValida();
        ['perZona' => $perZona, 'zoneLabel' => $zoneLabel, 'totale' => $totale, 'materialiPerIntervento' => $materialiPerIntervento] = $this->fetchGiornata($data);

        $giorni  = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
        $mesi    = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
        $ts      = strtotime($data);
        $dataLabel = $giorni[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mesi[(int) date('n', $ts)] . ' ' . date('Y', $ts);

        $html = view('operativo/viaggio/pdf', [
            'data'                   => $data,
            'dataLabel'              => $dataLabel,
            'perZona'                => $perZona,
            'zoneLabel'              => $zoneLabel,
            'totale'                 => $totale,
            'materialiPerIntervento' => $materialiPerIntervento,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('viaggio-' . $data . '.pdf', ['Attachment' => false]);
        exit;
    }

    // -------------------------------------------------------------------------

    private function dataValida(): string
    {
        $data = $this->request->getGet('data') ?? date('Y-m-d');
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : date('Y-m-d');
    }

    /**
     * Carica gli interventi pianificati per la data e li raggruppa per zona.
     */
    private function fetchGiornata(string $data): array
    {
        $interventi = (new InterventiModel())
            ->select("interventi.id, interventi.data_pianificata, interventi.durata_stimata,
                      interventi.descrizione, interventi.urgenza, interventi.priorita,
                      CASE WHEN c.tipo = 'persona_fisica'
                           THEN TRIM(CONCAT_WS(' ', c.cognome, c.nome))
                           ELSE c.ragsoc
                      END AS cliente_denominazione,
                      c.indirizzo, c.citta, c.zona AS cliente_zona,
                      TRIM(CONCAT_WS(' ', p.cognome, p.nome)) AS tecnico_nome,
                      ti.nome AS tipo_nome, ti.icona AS tipo_icona")
            ->join('clienti c',         'c.id  = interventi.cliente_id',        'left')
            ->join('personale p',        'p.id  = interventi.tecnico_id',         'left')
            ->join('tipi_intervento ti', 'ti.id = interventi.tipo_intervento_id', 'left')
            ->where("DATE(interventi.data_pianificata)", $data)
            ->where('interventi.stato !=', InterventiModel::STATO_ANNULLATO)
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();

        $ids = array_column($interventi, 'id');
        $materialiPerIntervento = [];
        if ($ids) {
            foreach ((new InterventiMaterialiModel())
                ->select('intervento_id, quantita, descrizione, note')
                ->whereIn('intervento_id', $ids)
                ->where('stato', 'da_portare')
                ->findAll() as $m) {
                $materialiPerIntervento[(int) $m['intervento_id']][] = $m;
            }
        }

        $zoneLabel = ClientiModel::ZONE_LABEL + ['nessuna' => 'Senza zona'];
        $perZona   = [];
        foreach ($interventi as $i) {
            $zona = ($i['cliente_zona'] !== null && $i['cliente_zona'] !== '')
                ? (int) $i['cliente_zona']
                : 'nessuna';
            $perZona[$zona][] = $i;
        }
        $zonaOrdini = [-1 => 0, 0 => 1, 1 => 2, 'nessuna' => 3];
        uksort($perZona, fn($a, $b) => ($zonaOrdini[$a] ?? 99) <=> ($zonaOrdini[$b] ?? 99));

        return [
            'perZona'                => $perZona,
            'zoneLabel'              => $zoneLabel,
            'totale'                 => count($interventi),
            'materialiPerIntervento' => $materialiPerIntervento,
        ];
    }
}
