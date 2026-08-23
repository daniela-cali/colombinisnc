<?php

/**
 * @var array                                  $persona
 * @var string                                 $email
 * @var array                                  $gruppi
 * @var array                                  $gruppi_correnti
 * @var array                                  $pastelli
 * @var array                                  $colori_usati
 * @var bool                                   $puoGestireAccount
 * @var bool                                   $puoEliminare
 * @var \CodeIgniter\Shield\Entities\User|null $user
 */
$this->extend('layouts/admin');
$nomeCognome = $persona['cognome'] . ' ' . $persona['nome'];
?>
<?= $this->section('title') ?>Modifica — <?= esc($nomeCognome) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/personale') ?>">Personale</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/personale/' . $persona['id']) ?>"><?= esc($nomeCognome) ?></a></li>
    <li class="breadcrumb-item active">Modifica</li>
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
                    <i class="bi bi-pencil me-2"></i>Modifica — <?= esc($nomeCognome) ?>
                </h3>
                <div class="card-tools d-flex gap-2">
                    <a href="<?= base_url('anagrafiche/personale/' . $persona['id']) ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Annulla
                    </a>
                    <?php if ($puoEliminare): ?>
                        <form action="<?= base_url('anagrafiche/personale/' . $persona['id'] . '/delete') ?>"
                              method="post" class="d-inline"
                              onsubmit="return confirm('Eliminare definitivamente questo dipendente e il suo account? Possibile solo se non ha interventi né assenze in archivio.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Elimina
                            </button>
                        </form>
                    <?php endif ?>
                    <button type="submit" form="form-update" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </div>
            <form id="form-update" action="<?= base_url('anagrafiche/personale/' . $persona['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">

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

                    <?php // Email, ruoli e password solo a chi ha personale.account: l'ufficio
                          // modifica l'anagrafica e le assenze, non i ruoli (spec §2.1). ?>
                    <?php if ($user && $puoGestireAccount): ?>

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

                        <p class="text-muted section-header mb-3"><i class="bi bi-shield me-1"></i> Gruppi <span class="text-danger">*</span></p>
                        <div class="row g-2 mb-2">
                            <?php
                            $gruppiSelezionati = old('gruppi', $gruppi_correnti);
                            ?>
                            <?php foreach ($gruppi as $key => $label): ?>
                                <div class="col-md-3 col-6">
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

                    <?php endif ?>

                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
