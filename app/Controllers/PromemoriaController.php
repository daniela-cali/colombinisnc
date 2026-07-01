<?php

namespace App\Controllers;

use App\Models\PromemoriaModel;
use CodeIgniter\HTTP\RedirectResponse;

class PromemoriaController extends BaseController
{
    /**
     * Salva il nuovo promemoria (form nel modal del calendario).
     */
    public function store(): RedirectResponse
    {
        if ($r = $this->soloGestori()) {
            return $r;
        }

        if (! $this->validate($this->regole())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new PromemoriaModel())->insert($this->request->getPost());

        return $this->ritorno('Promemoria creato.');
    }

    /**
     * Aggiorna il promemoria.
     */
    public function update(int $id): RedirectResponse
    {
        if ($r = $this->soloGestori()) {
            return $r;
        }

        $model = new PromemoriaModel();
        if (! $model->find($id)) {
            return redirect()->to('operativo/calendario')->with('error', 'Promemoria non trovato.');
        }

        if (! $this->validate($this->regole())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        return $this->ritorno('Promemoria aggiornato.');
    }

    /**
     * Elimina il promemoria (hard delete: nessun record collegato).
     */
    public function delete(int $id): RedirectResponse
    {
        if ($r = $this->soloGestori()) {
            return $r;
        }

        $model = new PromemoriaModel();
        if (! $model->find($id)) {
            return redirect()->to('operativo/calendario')->with('error', 'Promemoria non trovato.');
        }

        $model->delete($id);

        return $this->ritorno('Promemoria eliminato.');
    }

    // -------------------------------------------------------------------------

    /**
     * Redirect al calendario con messaggio (i promemoria si gestiscono da lì).
     */
    private function ritorno(string $messaggio): RedirectResponse
    {
        return redirect()->to('operativo/calendario')->with('success', $messaggio);
    }

    /**
     * Regole di validazione comuni a insert e update.
     * Le date arrivano dal campo datetime-local nel formato 'Y-m-d\TH:i'.
     */
    private function regole(): array
    {
        return [
            'titolo'          => 'required|max_length[150]',
            'data_ora_inizio' => 'required|valid_date[Y-m-d\TH:i]',
            'data_ora_fine'   => 'permit_empty|valid_date[Y-m-d\TH:i]',
            'note'            => 'permit_empty',
        ];
    }

    /**
     * Consente la gestione dei promemoria solo a ufficio e amministratori.
     * Restituisce un RedirectResponse se l'utente non è autorizzato, altrimenti null.
     */
    private function soloGestori(): ?RedirectResponse
    {
        if (! auth()->user()->inGroup('ufficio', 'admin', 'developer')) {
            return redirect()->to('operativo/calendario')
                ->with('error', 'Non hai i permessi per gestire i promemoria.');
        }

        return null;
    }
}
