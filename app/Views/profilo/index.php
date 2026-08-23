<?php

/**
 * @var array|null                             $persona scheda dipendente, assente per gli account senza (spec §2.3)
 * @var string                                 $email
 * @var array                                  $pastelli
 * @var array                                  $colori_usati
 * @var \CodeIgniter\Shield\Entities\User|null $user
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Il mio profilo<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Il mio profilo</li>
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
                <h3 class="card-title mb-0">
                    <i class="bi bi-person-circle me-2"></i>Il mio profilo
                </h3>
                <div class="card-tools d-flex gap-2">
                    <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Annulla
                    </a>
                    <button type="submit" form="form-profilo" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </div>
            <form id="form-profilo" action="<?= base_url('profilo/aggiorna') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">

                    <?php // Chi non ha una scheda dipendente — oggi nessuno, domani gli account
                          // cliente — vede solo il blocco account. ?>
                    <?php if ($persona): ?>

                        <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Anagrafica</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control"
                                    value="<?= esc(old('nome', $persona['nome'])) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cognome <span class="text-danger">*</span></label>
                                <input type="text" name="cognome" class="form-control"
                                    value="<?= esc(old('cognome', $persona['cognome'])) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="telefono" class="form-control"
                                    value="<?= esc(old('telefono', $persona['telefono'] ?? '')) ?>">
                            </div>
                            <div class="col-12">
                                <?php $this->setData(['coloreCorrente' => old('colore', $persona['colore'] ?? '')]); ?>
                                <?= $this->include('anagrafiche/personale/_colore_picker') ?>
                            </div>
                        </div>

                    <?php endif ?>

                    <?php if ($user): ?>

                        <p class="text-muted section-header mb-3"><i class="bi bi-key me-1"></i> Account di accesso</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= esc($user->username) ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= esc(old('email', $email)) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Nuova password
                                    <span class="text-muted small">(lascia vuoto per non cambiare)</span>
                                </label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Conferma password</label>
                                <input type="password" name="password_confirm" class="form-control">
                            </div>
                        </div>

                    <?php endif ?>

                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
