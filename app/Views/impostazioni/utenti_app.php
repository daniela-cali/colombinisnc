<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Utenti<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
        <li class="breadcrumb-item active">Utenti</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>Utenti
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($utenti)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun utente trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Nominativo</th>
                                    <th>Gruppi</th>
                                    <th class="text-center">Attivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti as $u): ?>
                                    <tr>
                                        <td><?= esc($u['username']) ?></td>
                                        <td class="text-muted">
                                            <?= esc(trim(($u['cognome'] ?? '') . ' ' . ($u['nome'] ?? '')) ?: '—') ?>
                                        </td>
                                        <td>
                                            <?php if ($u['gruppi']): ?>
                                                <?php foreach (explode(', ', $u['gruppi']) as $g): ?>
                                                    <span class="badge bg-secondary me-1"><?= esc(ucfirst($g)) ?></span>
                                                <?php endforeach ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($u['active']): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('impostazioni/utenti-app/' . $u['id'] . '/edit') ?>"
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
