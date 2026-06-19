<?php
/**
 * @var array $categorie       Righe da CategorieArticoliModel::tutteOrdinate()
 * @var int   $prossimoOrdine  max(ordine) + 1, usato come default nel form
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Categorie Articoli<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Categorie Articoli</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-6">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-tags me-2"></i>Categorie Articoli</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categorie)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessuna categoria configurata.</p>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:60px" class="text-center">Ord.</th>
                                <th>Nome</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorie as $c): ?>
                                <tr title="ID categoria: <?= $c['id'] ?>"><?php // ID utile per debug DB ?>
                                    <td class="text-center text-muted small"><?= esc($c['ordine']) ?></td>
                                    <td class="fw-semibold"><?= esc($c['nome']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#modal-edit"
                                                data-id="<?= $c['id'] ?>"
                                                data-nome="<?= esc($c['nome']) ?>"
                                                data-ordine="<?= $c['ordine'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= base_url('impostazioni/categorie-articoli/' . $c['id'] . '/delete') ?>"
                                              method="post" class="d-inline"
                                              onsubmit="return confirm('Eliminare la categoria \"<?= esc(addslashes($c['nome'])) ?>\"?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                <?php endif ?>
            </div>

            <div class="card-footer">
                <p class="text-muted small mb-2"><i class="bi bi-plus-circle me-1"></i> Aggiungi categoria</p>
                <form action="<?= base_url('impostazioni/categorie-articoli/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label form-label-sm">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control form-control-sm"
                                   value="<?= esc(old('nome')) ?>" maxlength="100"
                                   placeholder="es. Prodotti chimici">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Ordine</label>
                            <input type="number" name="ordine" class="form-control form-control-sm"
                                   value="<?= esc(old('ordine', $prossimoOrdine)) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-3">
            <a href="<?= base_url('impostazioni') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Indietro
            </a>
        </div>

    </div>
</div>

<!-- Modal modifica -->
<div class="modal fade" id="modal-edit" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="form-edit" method="post">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifica categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="edit-nome" class="form-control" maxlength="100" required>
                    </div>
                    <div>
                        <label class="form-label">Ordine</label>
                        <input type="number" name="ordine" id="edit-ordine" class="form-control" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salva</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('modal-edit').addEventListener('show.bs.modal', function (e) {
    var btn  = e.relatedTarget;
    var form = document.getElementById('form-edit');
    form.action = '<?= base_url('impostazioni/categorie-articoli/') ?>' + btn.dataset.id + '/update';
    document.getElementById('edit-nome').value   = btn.dataset.nome;
    document.getElementById('edit-ordine').value = btn.dataset.ordine;
});
</script>
<?= $this->endSection() ?>
