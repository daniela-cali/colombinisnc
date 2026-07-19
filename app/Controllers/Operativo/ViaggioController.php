<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\ClientiModel;
use App\Models\InterventiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\PersonaleModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ViaggioController extends BaseController
{
    /**
     * Vista giornaliera degli interventi pianificati, raggruppati per zona.
     */
    public function index(): string
    {
        helper('colore');

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
            'tecnici'                => (new PersonaleModel())->elencoPerGruppi(['tecnico']),
            'help_sezione'           => 'viaggio',
        ]);
    }

    /**
     * Genera il PDF del foglio viaggio per la data indicata.
     * Il parametro GET tecnico_id, se presente, limita il PDF al singolo tecnico
     * (foglio viaggio individuale, richiamato dal pill tecnico attivo in index()).
     */
    public function pdf(): void
    {
        $data      = $this->dataValida();
        $tecnicoId = $this->tecnicoIdValido();
        ['perZona' => $perZona, 'zoneLabel' => $zoneLabel, 'totale' => $totale, 'materialiPerIntervento' => $materialiPerIntervento] = $this->fetchGiornata($data, $tecnicoId);

        $giorni  = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
        $mesi    = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
        $ts      = strtotime($data);
        $dataLabel = $giorni[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mesi[(int) date('n', $ts)] . ' ' . date('Y', $ts);

        $tecnicoNome = null;
        if ($tecnicoId !== null) {
            $tecnico = (new PersonaleModel())->find($tecnicoId);
            $tecnicoNome = $tecnico ? trim($tecnico['cognome'] . ' ' . $tecnico['nome']) : null;
        }

        $html = view('operativo/viaggio/pdf', [
            'data'                   => $data,
            'dataLabel'              => $dataLabel,
            'perZona'                => $perZona,
            'zoneLabel'              => $zoneLabel,
            'totale'                 => $totale,
            'materialiPerIntervento' => $materialiPerIntervento,
            'tecnicoNome'            => $tecnicoNome,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $slugTecnico = $tecnicoNome ? '-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($tecnicoNome)), '-') : '';
        $nomeFile    = 'viaggio-' . $data . $slugTecnico . '.pdf';
        $dompdf->stream($nomeFile, ['Attachment' => false]);
        exit;
    }

    // -------------------------------------------------------------------------

    private function dataValida(): string
    {
        $data = $this->request->getGet('data') ?? date('Y-m-d');
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : date('Y-m-d');
    }

    private function tecnicoIdValido(): ?int
    {
        $tecnicoId = $this->request->getGet('tecnico_id');
        return ($tecnicoId !== null && $tecnicoId !== '') ? (int) $tecnicoId : null;
    }

    /**
     * Carica gli interventi pianificati per la data (ed eventualmente il singolo tecnico) e li raggruppa per zona.
     */
    private function fetchGiornata(string $data, ?int $tecnicoId = null): array
    {
        $interventi = (new InterventiModel())->perGiornata($data, $tecnicoId);

        $materialiPerIntervento = [];
        foreach ((new InterventiMaterialiModel())->daPortarePerInterventi(array_column($interventi, 'id')) as $m) {
            $materialiPerIntervento[(int) $m['intervento_id']][] = $m;
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
