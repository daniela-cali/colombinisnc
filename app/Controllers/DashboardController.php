<?php

namespace App\Controllers;

use App\Models\AbbonamentiModel;
use App\Models\InterventiMaterialiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;

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
            'urgenti'     => [],
            'abbonamenti' => [],
            'mieiOggi'    => [],
            'mieiUrgenti' => [],
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
        $data['countOggi'] = model(InterventiModel::class)
            ->where('DATE(data_pianificata)', date('Y-m-d'))
            ->where('stato', InterventiModel::STATO_PIANIFICATO)
            ->countAllResults();

        $data['urgenti'] = model(InterventiModel::class)
            ->select("interventi.id, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, clienti.citta, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.urgenza', 1)
            ->where('interventi.stato', InterventiModel::STATO_DA_PIANIFICARE)
            ->orderBy('interventi.data_scadenza', 'ASC')
            ->findAll(10);

        if (! $includiAbbonamenti) {
            return;
        }

        $data['abbonamenti'] = model(AbbonamentiModel::class)
            ->select("abbonamenti.id, abbonamenti.data_fine, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, tipi_intervento.nome AS tipo, DATEDIFF(abbonamenti.data_fine, CURDATE()) AS giorni_rimasti")
            ->join('clienti', 'clienti.id = abbonamenti.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = abbonamenti.tipo_intervento_id', 'left')
            ->where('abbonamenti.stato', 'attivo')
            ->where('abbonamenti.data_fine >=', date('Y-m-d'))
            ->where('abbonamenti.data_fine <=', date('Y-m-d', strtotime('+30 days')))
            ->orderBy('abbonamenti.data_fine', 'ASC')
            ->findAll();
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

        $data['mieiOggi'] = model(InterventiModel::class)
            ->select("interventi.id, interventi.data_pianificata, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, clienti.citta, clienti.indirizzo, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('DATE(interventi.data_pianificata)', date('Y-m-d'))
            ->where('interventi.stato', InterventiModel::STATO_PIANIFICATO)
            ->where('interventi.tecnico_id', $myId)
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();

        $data['mieiUrgenti'] = model(InterventiModel::class)
            ->select("interventi.id, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, clienti.citta, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.urgenza', 1)
            ->where('interventi.stato', InterventiModel::STATO_DA_PIANIFICARE)
            ->where('interventi.tecnico_id', $myId)
            ->orderBy('interventi.data_scadenza', 'ASC')
            ->findAll();
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
        $interventi = model(InterventiModel::class)
            ->select("interventi.id, interventi.data_pianificata, interventi.stato, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, clienti.indirizzo, clienti.citta, clienti.cap, clienti.lat, clienti.lng, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.tecnico_id', $myId)
            ->whereIn('interventi.stato', [InterventiModel::STATO_PIANIFICATO, InterventiModel::STATO_IN_CORSO])
            ->where('DATE(interventi.data_pianificata) >=', $dataInizio)
            ->where('DATE(interventi.data_pianificata) <=', $dataFine)
            ->orderBy('interventi.data_pianificata', 'ASC')
            ->findAll();

        // Materiali da portare di tutti gli interventi in un'unica query, raggruppati per intervento.
        $materialiPerIntervento = [];
        if ($interventi) {
            $ids = array_column($interventi, 'id');
            $materiali = model(InterventiMaterialiModel::class)
                ->select("interventi_materiali.intervento_id, interventi_materiali.quantita, COALESCE(a.descrizione, interventi_materiali.descrizione) AS desc_materiale")
                ->join('articoli a', 'a.id = interventi_materiali.articolo_id', 'left')
                ->whereIn('interventi_materiali.intervento_id', $ids)
                ->where('interventi_materiali.stato', InterventiMaterialiModel::STATO_DA_PORTARE)
                ->findAll();

            foreach ($materiali as $m) {
                $materialiPerIntervento[$m['intervento_id']][] = $m;
            }
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

        $urgenti = model(InterventiModel::class)
            ->select("interventi.id, COALESCE(clienti.ragsoc, TRIM(CONCAT_WS(' ', clienti.cognome, clienti.nome))) AS cliente_denominazione, clienti.citta, tipi_intervento.nome AS tipo")
            ->join('clienti', 'clienti.id = interventi.cliente_id')
            ->join('tipi_intervento', 'tipi_intervento.id = interventi.tipo_intervento_id', 'left')
            ->where('interventi.urgenza', 1)
            ->where('interventi.stato', InterventiModel::STATO_DA_PIANIFICARE)
            ->where('interventi.tecnico_id', $myId)
            ->orderBy('interventi.data_scadenza', 'ASC')
            ->findAll();

        return view('dashboard/tecnico', ['giorni' => $giorni, 'urgenti' => $urgenti, 'help_sezione' => 'dashboard_tecnico']);
    }
}
