<?php

namespace App\Controllers;

use App\Models\AbbonamentiModel;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;

class DashboardController extends BaseController
{
    /**
     * Dashboard principale — contenuto adattato al gruppo dell'utente loggato.
     */
    public function index(): string
    {
        $user = auth()->user();

        $isAdmin   = $user->inGroup('admin', 'developer');
        $isUfficio = $user->inGroup('ufficio');
        $isTecnico = $user->inGroup('tecnico');

        $data = [
            'isAdmin'     => $isAdmin,
            'isUfficio'   => $isUfficio,
            'isTecnico'   => $isTecnico,
            'countOggi'   => 0,
            'urgenti'     => [],
            'abbonamenti' => [],
            'mieiOggi'    => [],
            'mieiUrgenti' => [],
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
}
