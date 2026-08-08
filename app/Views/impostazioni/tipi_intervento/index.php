<?php
/**
 * @var array $tipi       Righe da TipiInterventoModel::tutti()
 * @var array $categorie  [codice => etichetta] da TipiInterventoModel::CATEGORIE_LABEL
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Tipi Intervento<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Tipi Intervento</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <!-- Lista tipi -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>Tipi Intervento</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tipi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun tipo configurato.</p>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">Ord.</th>
                                <th style="width:40px"></th>
                                <th>Nome</th>
                                <th>Sezione</th>
                                <th>Codice</th>
                                <th class="text-center">Prefisso</th>
                                <th class="text-center">Durata default</th>
                                <th class="text-center">Attivo</th>
                                <th class="text-center">Abbonabile</th>
                                <th class="text-center">Pul. fondo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipi as $t): ?>
                                <tr title="ID tipo intervento: <?= $t['id'] ?>"><?php // ID utile per debug DB ?>
                                    <td class="text-muted small text-center"><?= esc($t['ordine']) ?></td>
                                    <td class="text-center text-muted">
                                        <?php if ($t['icona']): ?>
                                            <i class="fas <?= esc($t['icona']) ?>"></i>
                                        <?php endif ?>
                                    </td>
                                    <td class="fw-semibold"><?= esc($t['nome']) ?></td>
                                    <td><span class="text-muted small"><?= esc($categorie[$t['categoria'] ?? 'generale'] ?? $t['categoria'] ?? '—') ?></span></td>
                                    <td><code class="text-muted"><?= esc($t['codice']) ?></code></td>
                                    <td class="text-center">
                                        <?php if ($t['prefisso_codice']): ?>
                                            <code class="small"><?= esc($t['prefisso_codice']) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted small">INT</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= $t['durata_default'] ?> min
                                        (<?= floor($t['durata_default'] / 60) ?>h <?= sprintf('%02d', $t['durata_default'] % 60) ?>′)
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['attivo']): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['abbonabile']): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-dash text-muted"></i>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['ha_pulizia_fondo']): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-dash text-muted"></i>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-edit"
                                                data-id="<?= $t['id'] ?>"
                                                data-codice="<?= esc($t['codice']) ?>"
                                                data-nome="<?= esc($t['nome']) ?>"
                                                data-categoria="<?= esc($t['categoria'] ?? 'generale') ?>"
                                                data-icona="<?= esc($t['icona'] ?? '') ?>"
                                                data-durata="<?= $t['durata_default'] ?>"
                                                data-ordine="<?= $t['ordine'] ?>"
                                                data-attivo="<?= $t['attivo'] ?>"
                                                data-abbonabile="<?= $t['abbonabile'] ?>"
                                                data-prefisso-codice="<?= esc($t['prefisso_codice'] ?? '') ?>"
                                                data-ha-pulizia-fondo="<?= $t['ha_pulizia_fondo'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php $nomeTipo = $t['nome']; ?>
                                        <form action="<?= base_url('impostazioni/tipi-intervento/' . $t['id'] . '/delete') ?>"
                                              method="post" class="d-inline"
                                              onsubmit="return confirm('Eliminare il tipo <?= esc(addslashes($nomeTipo)) ?>?')">
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

            <!-- Form aggiunta nuovo tipo -->
            <div class="card-footer">
                <p class="text-muted small mb-2"><i class="bi bi-plus-circle me-1"></i> Aggiungi tipo</p>
                <form action="<?= base_url('impostazioni/tipi-intervento/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Codice <span class="text-danger">*</span></label>
                            <input type="text" name="codice" class="form-control form-control-sm"
                                   value="<?= esc(old('codice')) ?>" maxlength="50"
                                   placeholder="es. impianti">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Prefisso</label>
                            <input type="text" name="prefisso_codice" class="form-control form-control-sm text-uppercase"
                                   value="<?= esc(old('prefisso_codice')) ?>" maxlength="3"
                                   placeholder="INT">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control form-control-sm"
                                   value="<?= esc(old('nome')) ?>" maxlength="100"
                                   placeholder="es. Impianti">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Sezione</label>
                            <select name="categoria" class="form-select form-select-sm">
                                <?php foreach ($categorie as $cod => $lab): ?>
                                    <option value="<?= esc($cod) ?>" <?= old('categoria', 'generale') === $cod ? 'selected' : '' ?>>
                                        <?= esc($lab) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 align-items-end mt-1">
                        <div class="col-md-4">
                            <?= view('impostazioni/tipi_intervento/_icona_picker', [
                                'suffix'      => 'new',
                                'valoreIcona' => old('icona', ''),
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Durata (min) <span class="text-danger">*</span></label>
                            <input type="number" name="durata_default" class="form-control form-control-sm"
                                   value="<?= esc(old('durata_default', 60)) ?>"
                                   min="5" max="480" step="5">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Ordine</label>
                            <input type="number" name="ordine" class="form-control form-control-sm"
                                   value="<?= esc(old('ordine', 0)) ?>" min="0">
                        </div>
                        <div class="col-auto d-flex align-items-end pb-1 gap-3">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="abbonabile" id="new-abbonabile" value="1">
                                <label class="form-check-label small" for="new-abbonabile">Abb.</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="ha_pulizia_fondo" id="new-ha-pulizia-fondo" value="1">
                                <label class="form-check-label small" for="new-ha-pulizia-fondo">Pul.</label>
                            </div>
                        </div>
                        <div class="col d-flex align-items-end">
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
<div class="modal fade" id="modal-edit" tabindex="-1"
     data-base-url="<?= base_url('impostazioni/tipi-intervento/') ?>">
    <div class="modal-dialog">
        <form id="form-edit" method="post">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifica tipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Codice <span class="text-danger">*</span></label>
                            <input type="text" name="codice" id="edit-codice" class="form-control" maxlength="50" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Prefisso</label>
                            <input type="text" name="prefisso_codice" id="edit-prefisso-codice"
                                   class="form-control text-uppercase" maxlength="3" placeholder="INT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="edit-nome" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <?= view('impostazioni/tipi_intervento/_icona_picker', [
                                'suffix'      => 'edit',
                                'valoreIcona' => '',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoria</label>
                            <select name="categoria" id="edit-categoria" class="form-select">
                                <?php foreach ($categorie as $cod => $lab): ?>
                                    <option value="<?= esc($cod) ?>"><?= esc($lab) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Durata (min)</label>
                            <input type="number" name="durata_default" id="edit-durata" class="form-control" min="5" max="480" step="5" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordine</label>
                            <input type="number" name="ordine" id="edit-ordine" class="form-control" min="0">
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="attivo" id="edit-attivo" value="1">
                                <label class="form-check-label" for="edit-attivo">Attivo</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abbonabile" id="edit-abbonabile" value="1">
                                <label class="form-check-label" for="edit-abbonabile">Può essere usato negli abbonamenti</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ha_pulizia_fondo" id="edit-ha-pulizia-fondo" value="1">
                                <label class="form-check-label" for="edit-ha-pulizia-fondo">Prevede opzione pulizia fondo</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check d-none" id="div-operazioni-standard">
                                <textarea name="edit-operazioni_standard" id="edit-operazioni_standard" class="form-control" rows="10"></textarea>
                                <label class="form-check-label" for="edit-operazioni_standard">Operazioni standard previste nell'abbonamento</label>

                            </div>
                        </div>
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
    var btn    = e.relatedTarget;
    var form   = document.getElementById('form-edit');
    var baseUrl = this.dataset.baseUrl;

    form.action = baseUrl + btn.dataset.id + '/update';

    document.getElementById('edit-codice').value  = btn.dataset.codice;
    document.getElementById('edit-nome').value    = btn.dataset.nome;
    document.getElementById('icona-edit').value   = btn.dataset.icona;
    document.getElementById('icona-preview-edit').className = 'fas ' + (btn.dataset.icona || 'fa-question');
    document.getElementById('edit-durata').value  = btn.dataset.durata;
    document.getElementById('edit-ordine').value  = btn.dataset.ordine;
    document.getElementById('edit-categoria').value = btn.dataset.categoria || 'generale';
    document.getElementById('edit-attivo').checked          = btn.dataset.attivo          === '1';
    document.getElementById('edit-abbonabile').checked      = btn.dataset.abbonabile      === '1';
    document.getElementById('edit-prefisso-codice').value      = btn.dataset.prefissoCodice  || '';
    document.getElementById('edit-ha-pulizia-fondo').checked   = btn.dataset.haPuliziaFondo  === '1';
    var div = document.getElementById('div-operazioni-standard');
    div.classList.toggle('d-none', !document.getElementById('edit-abbonabile').checked);
    
});
</script>
<?= $this->endSection() ?>
