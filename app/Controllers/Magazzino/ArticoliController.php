<?php

namespace App\Controllers\Magazzino;

use App\Controllers\BaseController;
use App\Models\ArticoliModel;
use App\Models\CategorieArticoliModel;
use App\Models\InterventiMaterialiModel;

class ArticoliController extends BaseController
{
    /**
     * Lista tutti gli articoli con categoria.
     */
    public function index(): string
    {
        return view('magazzino/articoli/index', [
            'articoli'     => (new ArticoliModel())->elencoCompleto(),
            'help_sezione' => 'articoli',
        ]);
    }

    /**
     * Form nuovo articolo.
     */
    public function nuovo(): string
    {
        return view('magazzino/articoli/nuovo', [
            'categorie'   => (new CategorieArticoliModel())->tutteOrdinate(),
            'unitaMisura' => ArticoliModel::UNITA_MISURA,
            'from'        => $this->request->getGet('from') ?? '',
        ]);
    }

    /**
     * Salva il nuovo articolo.
     */
    public function store()
    {
        if (! $this->validate($this->regoleValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new ArticoliModel())->insert(array_merge(
            $this->request->getPost(),
            ['attivo' => 1]
        ));

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'magazzino/articoli';

        return redirect()->to($dest)->with('success', 'Articolo aggiunto.');
    }

    /**
     * Form modifica articolo.
     */
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $articolo = (new ArticoliModel())->find($id);
        if (! $articolo) {
            return redirect()->to('magazzino/articoli')->with('error', 'Articolo non trovato.');
        }

        return view('magazzino/articoli/edit', [
            'articolo'    => $articolo,
            'categorie'   => (new CategorieArticoliModel())->tutteOrdinate(),
            'unitaMisura' => ArticoliModel::UNITA_MISURA,
            'from'        => $this->request->getGet('from') ?? '',
        ]);
    }

    /**
     * Aggiorna l'articolo.
     */
    public function update(int $id)
    {
        $model = new ArticoliModel();
        if (! $model->find($id)) {
            return redirect()->to('magazzino/articoli')->with('error', 'Articolo non trovato.');
        }

        if (! $this->validate($this->regoleValidazione())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model->update($id, array_merge(
            $this->request->getPost(),
            ['attivo' => (int) (bool) $this->request->getPost('attivo')]
        ));

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'magazzino/articoli';

        return redirect()->to($dest)->with('success', 'Articolo aggiornato.');
    }

    /**
     * Elimina l'articolo (hard delete).
     * Consentito solo se non è usato in interventi_materiali.
     */
    public function delete(int $id)
    {
        $model    = new ArticoliModel();
        $articolo = $model->find($id);
        if (! $articolo) {
            return redirect()->to('magazzino/articoli')->with('error', 'Articolo non trovato.');
        }

        $inUso = (new InterventiMaterialiModel())->contaPerArticolo($id);

        if ($inUso > 0) {
            return redirect()->to('magazzino/articoli/' . $id . '/edit')
                ->with('error', 'Impossibile eliminare: l\'articolo è usato in ' . $inUso . ' materiali di intervento. Disattivalo invece.');
        }

        $model->delete($id);

        $from = $this->request->getPost('from');
        $dest = ($from && str_starts_with($from, base_url())) ? $from : 'magazzino/articoli';

        return redirect()->to($dest)->with('success', 'Articolo eliminato.');
    }

    private function regoleValidazione(): array
    {
        return [
            'codice'       => 'required|max_length[50]',
            'descrizione'  => 'required|max_length[255]',
            'categoria_id' => 'permit_empty|is_natural_no_zero',
            'unita_misura' => 'required|in_list[' . implode(',', array_keys(ArticoliModel::UNITA_MISURA)) . ']',
            'costo'        => 'permit_empty|decimal',
            'vendita'      => 'permit_empty|decimal',
            'giacenza'     => 'permit_empty|decimal',
        ];
    }
}
