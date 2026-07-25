<?php

namespace App\Controllers\Operativo;

use App\Controllers\BaseController;
use App\Models\InterventiMaterialiModel;

class MaterialiController extends BaseController
{
    /**
     * Aggiunge un materiale: se c'è intervento_id è legato all'intervento, altrimenti è sospeso (solo cliente_id).
     */
    public function store()
    {
        $model = new InterventiMaterialiModel();

        if (! $this->validate([
            'cliente_id'    => 'required|is_natural_no_zero',
            'intervento_id' => 'permit_empty|is_natural_no_zero',
            'articolo_id'   => 'permit_empty|is_natural_no_zero',
            'descrizione'   => 'permit_empty|required_without[articolo_id]|max_length[255]',
            'quantita'      => 'required|is_natural_no_zero',
            'note'          => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $interventoId = (int) $this->request->getPost('intervento_id');
        $clienteId    = (int) $this->request->getPost('cliente_id');
        $from         = $this->request->getPost('from');
        $model->insert($this->request->getPost());

        if ($interventoId) {
            // Resta sull'edit dell'intervento (che a sua volta porta già "from" in query string).
            $dest = 'operativo/interventi/' . $interventoId . '/edit' . ($from ? '?from=' . urlencode($from) : '');
        } elseif ($from && str_starts_with($from, base_url())) {
            // Materiale sospeso aggiunto dal modal "prossima visita": torna alla pagina di provenienza.
            $dest = $from;
        } else {
            $dest = 'anagrafiche/clienti/' . $clienteId . '#sec-materiali';
        }

        $redirect = redirect()->to($dest)->with('success', 'Materiale aggiunto.');

        if ($this->request->getPost('riapri_step_materiali')) {
            $redirect = $redirect->with('mostra_step_materiali', true);
        }

        return $redirect;
    }

    /**
     * Elimina un materiale: torna all'intervento se era collegato, altrimenti alla scheda cliente.
     */
    public function delete(int $id)
    {
        $model     = new InterventiMaterialiModel();
        $materiale = $model->find($id);

        if (! $materiale) {
            return redirect()->back()->with('error', 'Materiale non trovato.');
        }

        $interventoId = $materiale['intervento_id'];
        $clienteId    = $materiale['cliente_id'];
        $from         = $this->request->getPost('from');
        $model->delete($id);

        if ($interventoId) {
            $dest = 'operativo/interventi/' . $interventoId . '/edit' . ($from ? '?from=' . urlencode($from) : '');
        } else {
            $dest = 'anagrafiche/clienti/' . $clienteId . '#sec-materiali';
        }

        return redirect()->to($dest)->with('success', 'Materiale eliminato.');
    }
}
