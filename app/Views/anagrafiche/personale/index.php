<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Personale<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item active">Personale</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>Personale
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('anagrafiche/personale/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo dipendente
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($personale)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun dipendente trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nominativo</th>
                                    <th>Telefono</th>
                                    <th>Account</th>
                                    <th>Gruppi</th>
                                    <th class="text-center">Attivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personale as $p): ?>
                                    <tr title="ID persona: <?= $p['id'] ?>"><?php // ID utile per debug DB ?>
                                        <td>
                                            <?php if ($p['colore']): ?>
                                                <span class="badge me-1 rounded-circle p-2"
                                                      style="background-color:<?= esc($p['colore']) ?>">
                                                </span>
                                            <?php endif ?>
                                            <a href="<?= base_url('anagrafiche/personale/' . $p['id']) ?>"
                                               class="text-body text-decoration-none js-row-open">
                                                <strong><?= esc($p['cognome']) ?></strong>
                                                <?= esc($p['nome']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?= esc($p['telefono'] ?? '—') ?></td>
                                        <td class="text-muted small"><?= esc($p['username'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($p['gruppi']): ?>
                                                <?php foreach (explode(', ', $p['gruppi']) as $g): ?>
                                                    <span class="badge bg-secondary me-1"><?= esc(ucfirst($g)) ?></span>
                                                <?php endforeach ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($p['attivo']): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('anagrafiche/personale/' . $p['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
