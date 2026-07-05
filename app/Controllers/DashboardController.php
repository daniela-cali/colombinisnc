<?php

namespace App\Controllers;

use App\Models\AbbonamentiModel;
use App\Models\AssenzeModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use App\Models\PromemoriaModel;

class DashboardController extends BaseController
{
    /**
     * Dashboard principale — contenuto adattato al gruppo dell'utente loggato.
     */
    public function index(): string
    {
        helper('acl');
        $user = auth()->user();

        $isAdmin   = $user->inGroup('admin', 'developer');
        $isUfficio = $user->inGroup('ufficio');
        $isTecnico = $user->inGroup('tecnico');

        // Tecnico puro → agenda mobile-first dedicata (3 giorni + materiali + mappa).
        // Chi è anche admin/ufficio resta sulla dashboard desktop con la sezione "i miei interventi".
        if (is_solo_tecnico()) {
            return $this->agendaTecnico((int) $user->id);
        }

        $data = [
            'isAdmin'     => $isAdmin,
            'isUfficio'   => $isUfficio,
            'isTecnico'   => $isTecnico,
            'countOggi'   => 0,
            'interventiOggi' => [],
            'urgenti'     => [],
            'abbonamenti' => [],
            'assentiOggi' => [],
            'tipiAssenzaLabel' => AssenzeModel::TIPI_LABEL,
            'mieiOggi'    => [],
            'mieiUrgenti' => [],
            'promemoria'  => model(PromemoriaModel::class)->inArrivoRaggruppati((int) $user->id),
            'help_sezione' => 'dashboard',
        ];

        if ($isAdmin || $isUfficio) {
            $this->caricaDatiUfficio($data, $isUfficio);
        }

        if ($isTecnico) {
            $this->caricaDatiTecnico($data, (int) $user->id);
        }

        return view('dashboard/index', $data);
    }

    /**
     * Dati per la sezione operativa visibile ad admin e ufficio.
     * Gli abbonamenti in scadenza vengono caricati solo per il gruppo ufficio.
     */
    private function caricaDatiUfficio(array &$data, bool $includiAbbonamenti): void
    {
        // Una sola query per gli interventi di oggi: il totale è count(), la lista mostra i primi 5.
        $oggi = model(InterventiModel::class)->agendaGiorno(date('Y-m-d'));
        $data['countOggi']      = count($oggi);
        $data['interventiOggi'] = array_slice($oggi, 0, 5);

        $data['urgenti']     = model(InterventiModel::class)->urgentiDaPianificare(null, 10);
        $data['assentiOggi'] = model(AssenzeModel::class)->oggi();

        if (! $includiAbbonamenti) {
            return;
        }

        $data['abbonamenti'] = model(AbbonamentiModel::class)->inScadenza(30);
    }

    /**
     * Dati personali per il tecnico loggato — interventi propri di oggi e urgenti assegnati.
     */
    private function caricaDatiTecnico(array &$data, int $userId): void
    {
        $myPersonale = model(PersonaleModel::class)->perUtente($userId);
        if (! $myPersonale) {
            return;
        }
        $myId = $myPersonale['id'];

        $data['mieiOggi']    = model(InterventiModel::class)->agendaGiorno(date('Y-m-d'), $myId);
        $data['mieiUrgenti'] = model(InterventiModel::class)->urgentiDaPianificare($myId);
    }

    /**
     * Agenda mobile-first del tecnico: interventi dei prossimi 3 giorni
     * (oggi, domani, dopodomani) con materiali da portare e coordinate per la mappa,
     * più gli urgenti non ancora pianificati assegnati.
     */
    private function agendaTecnico(int $userId): string
    {
        $myPersonale = model(PersonaleModel::class)->perUtente($userId);

        // Impalcatura dei 3 giorni: serve anche se il tecnico non ha una scheda personale,
        // così la view mostra comunque i tab vuoti invece di un errore.
        $giorni = [];
        foreach (['Oggi', 'Domani', 'Dopodomani'] as $n => $label) {
            $giorni[] = [
                'data'       => date('Y-m-d', strtotime("+{$n} days")),
                'label'      => $label,
                'interventi' => [],
            ];
        }

        if (! $myPersonale) {
            return view('dashboard/tecnico', ['giorni' => $giorni, 'urgenti' => [], 'help_sezione' => 'dashboard_tecnico']);
        }
        $myId = $myPersonale['id'];

        $dataInizio = $giorni[0]['data'];
        $dataFine   = $giorni[count($giorni) - 1]['data'];

        // Interventi attivi del tecnico nella finestra dei 3 giorni.
        $interventi = model(InterventiModel::class)->agendaTecnicoPeriodo($myId, $dataInizio, $dataFine);

        // Materiali da portare di tutti gli interventi in un'unica query, raggruppati per intervento.
        $materialiPerIntervento = [];
        foreach (model(InterventiMaterialiModel::class)->daPortarePerInterventi(array_column($interventi, 'id')) as $m) {
            $materialiPerIntervento[$m['intervento_id']][] = $m;
        }

        // Distribuisce ogni intervento (con i suoi materiali) nel giorno corrispondente.
        foreach ($interventi as $i) {
            $giorno = date('Y-m-d', strtotime($i['data_pianificata']));
            $i['materiali'] = $materialiPerIntervento[$i['id']] ?? [];
            foreach ($giorni as $k => $g) {
                if ($g['data'] === $giorno) {
                    $giorni[$k]['interventi'][] = $i;
                    break;
                }
            }
        }

        $urgenti = model(InterventiModel::class)->urgentiDaPianificare($myId);

        return view('dashboard/tecnico', ['giorni' => $giorni, 'urgenti' => $urgenti, 'help_sezione' => 'dashboard_tecnico']);
    }
}
