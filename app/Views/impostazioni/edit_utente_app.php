<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Modifica Utente<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('impostazioni/utenti-app') ?>">Utenti</a></li>
        <li class="breadcrumb-item active">Modifica</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-7">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-person-gear me-2"></i><?= esc($utente->username) ?>
                </h3>
            </div>
            <form action="<?= base_url('impostazioni/utenti-app/' . $utente->id . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= esc(old('email', $email)) ?>" required>
                        </div>

                        <div class="col-12">
                            <p class="text-muted section-header mb-3">
                                <i class="bi bi-shield me-1"></i> Gruppi
                            </p>
                            <div class="row g-2">
                                <?php $gruppiSelezionati = old('gruppi', $gruppi_correnti) ?>
                                <?php foreach ($gruppi as $key => $label): ?>
                                    <div class="col-md-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="gruppi[]" value="<?= esc($key) ?>"
                                                   id="gruppo_<?= esc($key) ?>"
                                                   <?= in_array($key, (array) $gruppiSelezionati) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="gruppo_<?= esc($key) ?>">
                                                <?= esc($label) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <p class="text-muted section-header mb-3">
                                <i class="bi bi-key me-1"></i> Cambio password
                                <span class="text-muted small">(lascia vuoto per non cambiare)</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nuova password</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conferma password</label>
                            <input type="password" name="password_confirm" class="form-control">
                        </div>

                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('impostazioni/utenti-app') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Annulla
                        </a>
                        <form action="<?= base_url('impostazioni/utenti-app/' . $utente->id . '/delete') ?>"
                              method="post" class="d-inline"
                              onsubmit="return confirm('Eliminare definitivamente questo utente?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Elimina
                            </button>
                        </form>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
