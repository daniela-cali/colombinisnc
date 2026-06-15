<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\InterventiMaterialiModel;

class MaterialiController extends BaseController
{
    /**
     * Aggiunge un materiale all'intervento e torna alla scheda (tab Materiali).
     */
    public function store()
    {
        $model = new InterventiMaterialiModel();

        if (! $this->validate([
            'intervento_id' => 'required|is_natural_no_zero',
            'descrizione'   => 'required|max_length[255]',
            'quantita'      => 'required|is_natural_no_zero',
            'note'          => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $interventoId = (int) $this->request->getPost('intervento_id');
        $model->insert($this->request->getPost());

        return redirect()->to('operativo/interventi/' . $interventoId . '/edit')
            ->with('success', 'Materiale aggiunto.');
    }

    /**
     * Elimina un materiale e torna alla scheda intervento.
     */
    public function delete(int $id)
    {
        $model     = new InterventiMaterialiModel();
        $materiale = $model->find($id);

        if (! $materiale) {
            return redirect()->back()->with('error', 'Materiale non trovato.');
        }

        $interventoId = $materiale['intervento_id'];
        $model->delete($id);

        return redirect()->to('operativo/interventi/' . $interventoId . '/edit')
            ->with('success', 'Materiale eliminato.');
    }
}
