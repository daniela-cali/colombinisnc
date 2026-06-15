<?php
/**
 * @var array                                  $persona
 * @var string                                 $email
 * @var array                                  $gruppi   Map chiave → label leggibile
 * @var \CodeIgniter\Shield\Entities\User|null $user
 */
$this->extend('layouts/admin');
$nomeCognome = esc($persona['cognome'] . ' ' . $persona['nome']);
?>
<?= $this->section('title') ?>Scheda — <?= $nomeCognome ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/personale') ?>">Personale</a></li>
    <li class="breadcrumb-item active"><?= $nomeCognome ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <?php if ($persona['colore']): ?>
                        <span class="badge rounded-circle p-2 me-1"
                              style="background-color:<?= esc($persona['colore']) ?>"></span>
                    <?php endif ?>
                    <i class="bi bi-person me-1"></i><?= $nomeCognome ?>
                    <?php if ($user && ! $user->active): ?>
                        <span class="badge bg-danger ms-2">Inattivo</span>
                    <?php endif ?>
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('anagrafiche/personale/' . $persona['id'] . '/edit') ?>"
                       class="btn btn-sm">
                        <i class="bi bi-pencil me-1"></i>Modifica
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">

                    <div class="col-12">
                        <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Anagrafica</p>
                        <dl class="row mb-0 small">
                            <dt class="col-sm-4 text-muted fw-normal">Nominativo</dt>
                            <dd class="col-sm-8 fw-semibold"><?= $nomeCognome ?></dd>

                            <dt class="col-sm-4 text-muted fw-normal">Telefono</dt>
                            <dd class="col-sm-8">
                                <?php if ($persona['telefono']): ?>
                                    <a href="tel:<?= esc($persona['telefono']) ?>"><?= esc($persona['telefono']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </dd>

                            <?php if ($persona['colore']): ?>
                                <dt class="col-sm-4 text-muted fw-normal">Colore profilo</dt>
                                <dd class="col-sm-8">
                                    <span class="badge rounded-circle p-2 me-1"
                                          style="background-color:<?= esc($persona['colore']) ?>"></span>
                                    <code class="text-muted"><?= esc($persona['colore']) ?></code>
                                </dd>
                            <?php endif ?>
                        </dl>
                    </div>

                    <?php if ($user): ?>
                        <div class="col-12">
                            <p class="text-muted section-header mb-3"><i class="bi bi-key me-1"></i> Account di accesso</p>
                            <dl class="row mb-0 small">
                                <dt class="col-sm-4 text-muted fw-normal">Username</dt>
                                <dd class="col-sm-8"><?= esc($user->username) ?></dd>

                                <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                                <dd class="col-sm-8">
                                    <?php if ($email): ?>
                                        <a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif ?>
                                </dd>

                                <dt class="col-sm-4 text-muted fw-normal">Gruppi</dt>
                                <dd class="col-sm-8">
                                    <?php foreach ($user->getGroups() as $g): ?>
                                        <span class="badge bg-secondary me-1"><?= esc($gruppi[$g] ?? ucfirst($g)) ?></span>
                                    <?php endforeach ?>
                                </dd>
                            </dl>
                        </div>
                    <?php endif ?>

                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="<?= base_url('anagrafiche/personale') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Torna al personale
            </a>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
