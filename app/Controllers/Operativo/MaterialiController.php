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
            'articolo_id'   => 'permit_empty|is_natural_no_zero',
            'descrizione'   => 'permit_empty|required_without[articolo_id]|max_length[255]',
            'quantita'      => 'required|is_natural_no_zero',
            'note'          => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $interventoId = (int) $this->request->getPost('intervento_id');
        $from         = $this->request->getPost('from');
        $model->insert($this->request->getPost());

        $editUrl = 'operativo/interventi/' . $interventoId . '/edit' . ($from ? '?from=' . urlencode($from) : '');
        return redirect()->to($editUrl)->with('success', 'Materiale aggiunto.');
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
        $from         = $this->request->getPost('from');
        $model->delete($id);

        $editUrl = 'operativo/interventi/' . $interventoId . '/edit' . ($from ? '?from=' . urlencode($from) : '');
        return redirect()->to($editUrl)->with('success', 'Materiale eliminato.');
    }
}
