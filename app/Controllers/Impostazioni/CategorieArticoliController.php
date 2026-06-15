<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Models\CategorieArticoliModel;
use App\Models\ArticoliModel;

class CategorieArticoliController extends BaseController
{
    /**
     * Lista categorie con form inline di creazione.
     */
    public function index(): string
    {
        $categorie      = (new CategorieArticoliModel())->tutteOrdinate();
        $prossimoOrdine = $categorie ? max(array_column($categorie, 'ordine')) + 1 : 0;

        return view('impostazioni/categorie_articoli/index', [
            'categorie'      => $categorie,
            'prossimoOrdine' => $prossimoOrdine,
        ]);
    }

    /**
     * Crea una nuova categoria.
     */
    public function store()
    {
        if (! $this->validate([
            'nome'   => 'required|max_length[100]',
            'ordine' => 'permit_empty|is_natural',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new CategorieArticoliModel())->insert($this->request->getPost());

        return redirect()->to('impostazioni/categorie-articoli')->with('success', 'Categoria aggiunta.');
    }

    /**
     * Aggiorna nome e ordine della categoria.
     */
    public function update(int $id)
    {
        $model = new CategorieArticoliModel();
        if (! $model->find($id)) {
            return redirect()->to('impostazioni/categorie-articoli')->with('error', 'Categoria non trovata.');
        }

        if (! $this->validate([
            'nome'   => 'required|max_length[100]',
            'ordine' => 'permit_empty|is_natural',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, $this->request->getPost());

        return redirect()->to('impostazioni/categorie-articoli')->with('success', 'Categoria aggiornata.');
    }

    /**
     * Elimina la categoria solo se non ha articoli collegati.
     */
    public function delete(int $id)
    {
        $model = new CategorieArticoliModel();

        if (! $model->find($id)) {
            return redirect()->to('impostazioni/categorie-articoli')->with('error', 'Categoria non trovata.');
        }

        $inUso = (new ArticoliModel())->where('categoria_id', $id)->countAllResults();
        if ($inUso > 0) {
            return redirect()->to('impostazioni/categorie-articoli')
                ->with('error', 'Impossibile eliminare: la categoria contiene ' . $inUso . ' articoli.');
        }

        $model->delete($id);

        return redirect()->to('impostazioni/categorie-articoli')->with('success', 'Categoria eliminata.');
    }
}
