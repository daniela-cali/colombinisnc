<?php
/**
 * @var array $gruppi
 * @var array $pastelli
 * @var array $colori_usati
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Nuovo Dipendente<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/personale') ?>">Personale</a></li>
        <li class="breadcrumb-item active">Nuovo</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

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
                <h3 class="card-title mb-0"><i class="bi bi-person-plus me-2"></i>Nuovo dipendente</h3>
            </div>
            <form action="<?= base_url('anagrafiche/personale/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">

                    <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Anagrafica</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control"
                                   value="<?= esc(old('nome')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cognome <span class="text-danger">*</span></label>
                            <input type="text" name="cognome" class="form-control"
                                   value="<?= esc(old('cognome')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="<?= esc(old('telefono')) ?>">
                        </div>
                        <div class="col-12">
                            <?php
                            // Preseleziona il primo colore libero della palette
                            $coloreCorrente = old('colore');
                            if (! $coloreCorrente) {
                                $liberi = array_values(array_diff($pastelli, $colori_usati));
                                $coloreCorrente = $liberi[0] ?? $pastelli[0];
                            }
                            $this->setData(['coloreCorrente' => $coloreCorrente]);
                            ?>
                            <?= $this->include('anagrafiche/personale/_colore_picker') ?>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-key me-1"></i> Account di accesso</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= esc(old('username')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= esc(old('email')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conferma password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-shield me-1"></i> Gruppi <span class="text-danger">*</span></p>
                    <div class="row g-2">
                        <?php foreach ($gruppi as $key => $label): ?>
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="gruppi[]" value="<?= esc($key) ?>"
                                           id="gruppo_<?= esc($key) ?>"
                                           <?= in_array($key, (array) old('gruppi', [])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="gruppo_<?= esc($key) ?>">
                                        <?= esc($label) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>

                </div>
                <div class="card-footer card-azioni">
                    <a href="<?= base_url('anagrafiche/personale') ?>"
                       class="btn btn-sm btn-outline-secondary azione-ritorno">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary azione-primaria">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
