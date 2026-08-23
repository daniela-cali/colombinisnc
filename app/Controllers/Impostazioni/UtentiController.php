<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\InterventiModel;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class UtentiController extends BaseController
{
    private array $gruppiApp = [
        'admin'     => 'Amministratore',
        'developer' => 'Sviluppatore',
        'ufficio'   => 'Ufficio',
        'tecnico'   => 'Tecnico',
        'cliente'   => 'Cliente',
    ];

    /**
     * Vista d'insieme di tutti gli account: chi ha accesso, con quali ruoli,
     * e chi è ancora attivo. Di sola consultazione — email, ruoli e password si
     * modificano dalla scheda del dipendente, o dal proprio profilo.
     *
     * È l'unico elenco che mostra anche gli account senza scheda personale, che
     * in Anagrafiche > Personale non compaiono per definizione.
     * Vedi docs/spec/gestione_account_spec.md §2.2.
     */
    public function utentiApp(): string
    {
        return view('impostazioni/utenti_app', [
            'utenti'       => (new UserModel())->tuttiConGruppi(),
            'gruppi'       => $this->gruppiApp,
            'help_sezione' => 'utenti',
        ]);
    }

    /**
     * Sospende o riapre l'accesso di un account, senza toccare la scheda del dipendente
     * né il suo storico. È l'unico modo per escludere qualcuno dal gestionale: un account
     * non si elimina mai, perché gli interventi passati devono restare attribuiti.
     *
     * Usa ban()/unban() e non il campo `active`, che in questo progetto non impedisce
     * nulla: isActivated() (Shield, Traits/Activatable.php:22) è sempre vero quando non
     * è configurata un'azione di registrazione, e in Config\Auth $actions['register'] è
     * null. Il ban invece viene verificato per primo dal filtro SessionAuth, che disconnette
     * subito mostrando il messaggio.
     */
    public function cambiaStatoUtenteApp(int $id): RedirectResponse
    {
        $users = new UserModel();
        $user  = $users->findById($id);

        if (! $user) {
            return redirect()->to('impostazioni/utenti-app')->with('error', 'Utente non trovato.');
        }

        if ($id === user_id()) {
            return redirect()->to('impostazioni/utenti-app')
                ->with('error', 'Non puoi sospendere l\'account con cui stai lavorando.');
        }

        if ($user->isBanned()) {
            $user->unban();

            return redirect()->to('impostazioni/utenti-app')
                ->with('success', 'Accesso di "' . $user->username . '" riattivato.');
        }

        // Il messaggio viene mostrato all'interessato quando tenta di entrare.
        $user->ban('Il tuo accesso è stato sospeso. Rivolgiti a un amministratore.');

        $redirect = redirect()->to('impostazioni/utenti-app')
            ->with('success', 'Accesso di "' . $user->username . '" sospeso.');

        // Gli interventi già assegnati restano suoi: nessuno li tocca automaticamente,
        // ma da adesso non comparirà più fra i tecnici assegnabili, quindi chi resta
        // in sospeso va spostato a mano. L'avviso qui è il promemoria immediato; quello
        // che dura è la card "Interventi in conflitto" della dashboard, che li ripropone
        // a ogni accesso finché non sono stati riassegnati.
        $persona = (new PersonaleModel())->perUtente($id);
        $aperti  = $persona ? (new InterventiModel())->apertiPerTecnico((int) $persona['id']) : [];
        $quanti  = count($aperti);

        if ($quanti > 0) {
            $redirect->with('warning', 'Attenzione: ha ancora ' . $quanti . ' intervent' . ($quanti === 1 ? 'o' : 'i')
                . ' da completare, da riassegnare a un altro tecnico — ' . $this->elencoInterventi($aperti)
                . ' Li ritrovi nella card "Interventi in conflitto" della dashboard.');
        }

        return $redirect;
    }

    /**
     * Rende gli interventi da riassegnare come link alla loro modifica, per poterci andare
     * direttamente dall'avviso. Si ferma ai primi cinque: oltre, un messaggio flash lungo
     * una pagina nasconderebbe l'avviso invece di darlo — il resto sta nella dashboard.
     *
     * L'escape è qui perché il layout stampa i flash senza filtrarli (deve poterci mettere
     * i link): tutto ciò che arriva dal database va escapato prima di entrarci.
     */
    private function elencoInterventi(array $interventi): string
    {
        $primi = array_slice($interventi, 0, 5);

        $voci = array_map(static function (array $i): string {
            $etichetta = esc($i['cliente_denominazione']);
            if ($i['data_pianificata']) {
                $etichetta .= ' (' . date('d/m/Y', strtotime($i['data_pianificata'])) . ')';
            }

            return '<a href="' . base_url('operativo/interventi/' . $i['id'] . '/edit') . '">' . $etichetta . '</a>';
        }, $primi);

        $elenco = implode(', ', $voci);
        $altri  = count($interventi) - count($primi);

        return $altri > 0 ? $elenco . ' e altri ' . $altri . '.' : $elenco . '.';
    }
}
