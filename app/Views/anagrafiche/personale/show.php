<?php
/**
 * @var array                                  $persona
 * @var string                                 $email
 * @var array                                  $gruppi            Map chiave → label leggibile
 * @var \CodeIgniter\Shield\Entities\User|null $user
 * @var array                                  $assenze           Da AssenzeModel::perPersonale()
 * @var array                                  $tipiAssenzaLabel  AssenzeModel::TIPI_LABEL
 * @var array                                  $tipiAssenzaBadge  AssenzeModel::TIPI_BADGE
 * @var bool                                   $puoGestireAssenze
 */
$this->extend('layouts/admin');
$nomeCognome = esc($persona['cognome'] . ' ' . $persona['nome']);
$assenzeUrl  = base_url('anagrafiche/personale/' . $persona['id']) . '#sec-assenze';
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
                                    <span class="badge p-2 me-1"
                                          style="background-color:<?= esc($persona['colore']) ?>">
                                        <code style="color:<?= colore_testo($persona['colore']) ?>"><?= esc($persona['colore']) ?></code>
                                    </span>
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

        <!-- Assenze -->
        <div class="card card-outline card-warning mt-3" id="sec-assenze">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-calendar-x me-2"></i>Assenze
                    <span class="badge bg-secondary ms-1"><?= count($assenze) ?></span>
                </h3>
            </div>
            <div class="card-body">

                <?php if (! empty($assenze)): ?>
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($assenze as $a): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                <div class="me-2">
                                    <span class="badge <?= esc($tipiAssenzaBadge[$a['tipo']] ?? 'bg-secondary') ?> me-2">
                                        <?= esc($tipiAssenzaLabel[$a['tipo']] ?? ucfirst($a['tipo'])) ?>
                                    </span>
                                    <span class="small">
                                        <?= esc(date('d/m/Y', strtotime($a['data_inizio']))) ?>
                                        <?php if ($a['data_fine'] !== $a['data_inizio']): ?>
                                            – <?= esc(date('d/m/Y', strtotime($a['data_fine']))) ?>
                                        <?php endif ?>
                                    </span>
                                    <?php if (! empty($a['note'])): ?>
                                        <div class="text-muted small text-preline"><?= esc($a['note']) ?></div>
                                    <?php endif ?>
                                </div>
                                <?php if ($puoGestireAssenze): ?>
                                <form method="post"
                                      action="<?= base_url('anagrafiche/personale/assenze/' . $a['id'] . '/elimina') ?>"
                                      class="d-inline" onsubmit="return confirm('Eliminare questa assenza?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="from" value="<?= esc($assenzeUrl) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina assenza">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-4">Nessuna assenza registrata.</p>
                <?php endif ?>

                <?php if ($puoGestireAssenze): ?>
                <!-- Mini-form aggiunta assenza -->
                <form method="post" action="<?= base_url('anagrafiche/personale/assenze/aggiungi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="personale_id" value="<?= $persona['id'] ?>">
                    <input type="hidden" name="from" value="<?= esc($assenzeUrl) ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <?php foreach ($tipiAssenzaLabel as $val => $label): ?>
                                    <option value="<?= esc($val) ?>"><?= esc($label) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Dal</label>
                            <input type="date" name="data_inizio" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Al</label>
                            <input type="date" name="data_fine" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Note</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Facoltative">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif ?>

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
