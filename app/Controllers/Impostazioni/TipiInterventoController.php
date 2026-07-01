<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\InterventiModel;
use App\Models\TipiInterventoModel;

class TipiInterventoController extends BaseController
{
    /**
     * Lista tipi intervento con form inline per nuovo tipo.
     */
    public function index(): string
    {
        return view('impostazioni/tipi_intervento/index', [
            'tipi'         => (new TipiInterventoModel())->tutti(),
            'categorie'    => TipiInterventoModel::CATEGORIE_LABEL,
            'help_sezione' => 'tipi_intervento',
        ]);
    }

    /**
     * Salva un nuovo tipo intervento.
     */
    public function store()
    {
        if (! $this->validate($this->regolaValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new TipiInterventoModel())->insert($this->request->getPost());

        return redirect()->to('impostazioni/tipi-intervento')
            ->with('success', 'Tipo intervento aggiunto.');
    }

    /**
     * Aggiorna un tipo intervento esistente.
     */
    public function update(int $id)
    {
        $model = new TipiInterventoModel();

        if (! $model->find($id)) {
            return redirect()->to('impostazioni/tipi-intervento')->with('error', 'Tipo non trovato.');
        }

        if (! $this->validate($this->regolaValidazione($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        return redirect()->to('impostazioni/tipi-intervento')
            ->with('success', 'Tipo intervento aggiornato.');
    }

    /**
     * Elimina un tipo intervento.
     * Bloccato se ci sono interventi collegati.
     */
    public function delete(int $id)
    {
        $model = new TipiInterventoModel();
        $tipo  = $model->find($id);

        if (! $tipo) {
            return redirect()->to('impostazioni/tipi-intervento')->with('error', 'Tipo non trovato.');
        }

        $usato = (new InterventiModel())->contaPerTipo($id);

        if ($usato > 0) {
            return redirect()->to('impostazioni/tipi-intervento')
                ->with('error', 'Impossibile eliminare "' . esc($tipo['nome']) . '": è usato in ' . $usato . ' intervento/i.');
        }

        $model->delete($id);

        return redirect()->to('impostazioni/tipi-intervento')
            ->with('success', '"' . esc($tipo['nome']) . '" eliminato.');
    }

    // -------------------------------------------------------------------------

    private function regolaValidazione(?int $id = null): array
    {
        $unicoCodice = 'is_unique[tipi_intervento.codice' . ($id ? ",id,{$id}" : '') . ']';

        $categorieAmmesse = implode(',', array_keys(TipiInterventoModel::CATEGORIE_LABEL));

        return [
            'codice'         => 'required|max_length[50]|alpha_dash|' . $unicoCodice,
            'nome'           => 'required|max_length[100]',
            'categoria'      => 'permit_empty|in_list[' . $categorieAmmesse . ']',
            'icona'          => 'permit_empty|max_length[50]',
            'durata_default' => 'required|is_natural_no_zero',
            'ordine'         => 'permit_empty|is_natural',
        ];
    }
}
