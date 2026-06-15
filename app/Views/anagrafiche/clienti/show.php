<?php
/**
 * @var array $cliente     Record clienti con tutti i campi
 * @var array $interventi  Righe da InterventiModel::perCliente()
 * @var array $generiLabel Map genere → label leggibile
 * @var array $statiLabel  Map stato  → label leggibile
 */
$this->extend('layouts/admin');
$denom = \App\Models\ClientiModel::denominazione($cliente);

$zonaLabels = ['-1' => 'Ventimiglia', '0' => 'Ceriale', '1' => 'Savona'];
$zonaLabel  = ($cliente['zona'] !== null)
    ? ($zonaLabels[(string)(int)$cliente['zona']] ?? '—')
    : '—';

$statoBadge = [
    'da_pianificare' => 'bg-secondary',
    'pianificato'    => 'bg-primary',
    'in_corso'       => 'bg-warning text-dark',
    'completato'     => 'bg-success',
    'annullato'      => 'bg-danger',
];
?>
<?= $this->section('title') ?>Scheda — <?= esc($denom) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/clienti') ?>">Clienti</a></li>
    <li class="breadcrumb-item active"><?= esc($denom) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-person me-2"></i><?= esc($denom) ?>
                    <span class="badge ms-2 <?= $cliente['attivo'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $cliente['attivo'] ? 'Attivo' : 'Inattivo' ?>
                    </span>
                    <?php if ($cliente['codice']): ?>
                        <span class="text-muted small ms-2"><?= esc($cliente['codice']) ?></span>
                    <?php endif ?>
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('operativo/interventi/nuovo?cliente_id=' . $cliente['id'] . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#pane-interventi')) ?>"
                       class="btn btn-sm me-1">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                    <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/edit') ?>"
                       class="btn btn-sm">
                        <i class="bi bi-pencil me-1"></i>Modifica
                    </a>
                </div>
            </div>

            <!-- Tab nav -->
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="schedaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-anagrafica" data-bs-toggle="tab"
                                data-bs-target="#pane-anagrafica" type="button" role="tab">
                            <i class="bi bi-person-lines-fill me-1"></i>Anagrafica
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-interventi" data-bs-toggle="tab"
                                data-bs-target="#pane-interventi" type="button" role="tab">
                            <i class="bi bi-tools me-1"></i>Interventi
                            <?php if ($interventi): ?>
                                <span class="badge bg-secondary ms-1"><?= count($interventi) ?></span>
                            <?php endif ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-materiali" data-bs-toggle="tab"
                                data-bs-target="#pane-materiali" type="button" role="tab">
                            <i class="bi bi-box-seam me-1"></i>Materiali
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">

                <!-- TAB: Anagrafica (read-only) -->
                <div class="tab-pane fade show active" id="pane-anagrafica" role="tabpanel">
                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-6">
                                <p class="text-muted section-header mb-3"><i class="bi bi-building me-1"></i> Anagrafica</p>
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-5 text-muted fw-normal">Tipo</dt>
                                    <dd class="col-sm-7"><?= $cliente['tipo'] === 'societa' ? 'Società / Ditta' : 'Persona fisica' ?></dd>

                                    <?php if ($cliente['tipo'] === 'societa'): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Ragione sociale</dt>
                                        <dd class="col-sm-7 fw-semibold"><?= esc($cliente['ragsoc'] ?? '—') ?></dd>
                                    <?php else: ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Nome</dt>
                                        <dd class="col-sm-7 fw-semibold"><?= esc(trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''))) ?></dd>
                                    <?php endif ?>

                                    <?php if ($cliente['piva']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">P.IVA</dt>
                                        <dd class="col-sm-7"><?= esc($cliente['piva']) ?></dd>
                                    <?php endif ?>

                                    <?php if ($cliente['cfisc']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Cod. fiscale</dt>
                                        <dd class="col-sm-7"><?= esc($cliente['cfisc']) ?></dd>
                                    <?php endif ?>

                                    <?php if ($cliente['codice_esterno']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Cod. esterno</dt>
                                        <dd class="col-sm-7"><code><?= esc($cliente['codice_esterno']) ?></code></dd>
                                    <?php endif ?>
                                </dl>
                            </div>

                            <div class="col-md-6">
                                <p class="text-muted section-header mb-3"><i class="bi bi-telephone me-1"></i> Contatti</p>
                                <dl class="row mb-0 small">
                                    <?php if ($cliente['indirizzo'] || $cliente['citta']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Indirizzo</dt>
                                        <dd class="col-sm-7">
                                            <?= esc($cliente['indirizzo'] ?? '') ?>
                                            <?php if ($cliente['cap'] || $cliente['citta']): ?>
                                                <br><?= esc($cliente['cap'] ?? '') ?> <?= esc($cliente['citta'] ?? '') ?><?= $cliente['provincia'] ? ' (' . esc($cliente['provincia']) . ')' : '' ?>
                                            <?php endif ?>
                                        </dd>
                                    <?php endif ?>

                                    <?php if ($cliente['telefono']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Telefono</dt>
                                        <dd class="col-sm-7"><a href="tel:<?= esc($cliente['telefono']) ?>"><?= esc($cliente['telefono']) ?></a></dd>
                                    <?php endif ?>

                                    <?php if ($cliente['email']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Email</dt>
                                        <dd class="col-sm-7"><a href="mailto:<?= esc($cliente['email']) ?>"><?= esc($cliente['email']) ?></a></dd>
                                    <?php endif ?>

                                    <?php if ($cliente['contatti']): ?>
                                        <dt class="col-sm-5 text-muted fw-normal">Altri contatti</dt>
                                        <dd class="col-sm-7 text-preline"><?= esc($cliente['contatti']) ?></dd>
                                    <?php endif ?>
                                </dl>
                            </div>

                            <div class="col-12">
                                <p class="text-muted section-header mb-3"><i class="bi bi-sliders me-1"></i> Gestione</p>
                                <div class="row g-3 small">
                                    <div class="col-md-4">
                                        <span class="text-muted d-block">Zona</span>
                                        <strong><?= esc($zonaLabel) ?></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted d-block">Tecnico preferito</span>
                                        <strong><?= esc($cliente['tecnico_preferito_nome'] ?? '—') ?></strong>
                                    </div>
                                    <?php if ($cliente['distanza_sede'] !== null): ?>
                                        <div class="col-md-4">
                                            <span class="text-muted d-block">Distanza sede</span>
                                            <strong><?= esc($cliente['distanza_sede']) ?> km</strong>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>

                            <?php if ($cliente['note']): ?>
                                <div class="col-12">
                                    <p class="text-muted section-header mb-2"><i class="bi bi-sticky me-1"></i> Note</p>
                                    <div class="p-3 rounded border small text-preline"><?= esc($cliente['note']) ?></div>
                                </div>
                            <?php endif ?>

                        </div>
                    </div>
                </div>

                <!-- TAB: Interventi -->
                <div class="tab-pane fade" id="pane-interventi" role="tabpanel">
                    <div class="card-body">

                        <!-- Filter pills -->
                        <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
                            <button class="btn btn-sm btn-outline-primary" data-filtro="aperti">
                                <i class="bi bi-folder2-open me-1"></i>Aperti
                            </button>
                            <button class="btn btn-sm btn-outline-success" data-filtro="completati">
                                <i class="bi bi-check-circle me-1"></i>Completati
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-filtro="annullati">
                                <i class="bi bi-x-circle me-1"></i>Annullati
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" data-filtro="tutti">
                                Tutti (<?= count($interventi) ?>)
                            </button>
                            <a href="<?= base_url('operativo/interventi/nuovo?cliente_id=' . $cliente['id'] . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#pane-interventi')) ?>"
                               class="btn btn-sm btn-outline-success ms-auto">
                                <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table id="tbl-interventi" class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codice</th>
                                        <th>Tipo</th>
                                        <th>Genere</th>
                                        <th>Tecnico</th>
                                        <th>Data pianificata</th>
                                        <th>Scadenza</th>
                                        <th>Stato</th>
                                        <th></th><!-- stato raw — nascosto, usato dal filtro -->
                                        <th></th><!-- azioni -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interventi as $iv): ?>
                                        <tr>
                                            <td><code class="text-muted small"><?= esc($iv['codice']) ?></code></td>
                                            <td>
                                                <?php if ($iv['tipo_intervento_icona']): ?>
                                                    <i class="fas <?= esc($iv['tipo_intervento_icona']) ?> me-1 text-muted"></i>
                                                <?php endif ?>
                                                <?= esc($iv['tipo_intervento_nome'] ?? '—') ?>
                                            </td>
                                            <td class="text-muted small"><?= esc($generiLabel[$iv['genere']] ?? $iv['genere']) ?></td>
                                            <td class="text-muted small"><?= esc($iv['tecnico_nome'] ?? '—') ?></td>
                                            <td data-order="<?= esc($iv['data_pianificata'] ?? '') ?>">
                                                <?= $iv['data_pianificata'] ? esc(date('d/m/Y', strtotime($iv['data_pianificata']))) : '—' ?>
                                            </td>
                                            <td data-order="<?= esc($iv['data_scadenza'] ?? '') ?>">
                                                <?= $iv['data_scadenza'] ? esc(date('d/m/Y', strtotime($iv['data_scadenza']))) : '—' ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statoBadge[$iv['stato']] ?? 'bg-secondary' ?>">
                                                    <?= esc($statiLabel[$iv['stato']] ?? $iv['stato']) ?>
                                                </span>
                                                <?php if ($iv['urgenza']): ?>
                                                    <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Urgente"></i>
                                                <?php endif ?>
                                            </td>
                                            <td><?= esc($iv['stato']) ?></td>
                                            <td class="text-end">
                                                <a href="<?= base_url('operativo/interventi/' . $iv['id'] . '/edit')
                                                    . '?from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#pane-interventi') ?>"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- TAB: Materiali (placeholder) -->
                <div class="tab-pane fade" id="pane-materiali" role="tabpanel">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-box-seam" style="font-size:2.5rem"></i>
                        <p class="mt-3 mb-0">Lo storico materiali sarà disponibile in una versione futura.</p>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>

        <div class="mt-3">
            <a href="<?= base_url('anagrafiche/clienti') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Torna ai clienti
            </a>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script>
$(function () {
    var table = $('#tbl-interventi').DataTable({
        pageLength: 15,
        order: [[4, 'desc']],
        columnDefs: [
            { targets: 7, visible: false },
            { targets: 8, orderable: false, searchable: false }
        ],
        language: {
            emptyTable:   'Nessun intervento registrato.',
            info:         'Da _START_ a _END_ di _TOTAL_',
            infoEmpty:    'Nessun risultato',
            infoFiltered: '(filtrati da _MAX_ totali)',
            lengthMenu:   'Mostra _MENU_ righe',
            search:       'Cerca:',
            paginate:     { first: '«', last: '»', next: '›', previous: '‹' },
            zeroRecords:  'Nessun intervento trovato.'
        }
    });

    document.getElementById('tab-interventi').addEventListener('shown.bs.tab', function () {
        table.columns.adjust().draw(false);
    });

    var filtri = {
        aperti:    { q: '^(da_pianificare|pianificato|in_corso)$', regex: true  },
        completati:{ q: '^completato$',                        regex: true  },
        annullati: { q: '^annullato$',                         regex: true  },
        tutti:     { q: '',                                    regex: false }
    };

    function setFiltro(nome) {
        var f = filtri[nome];
        table.column(7).search(f.q, f.regex, false).draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('aperti');

    // Attiva il tab corrispondente all'anchor dell'URL (usato dal sistema ?from=)
    var hash = location.hash;
    if (hash) {
        var trigger = document.querySelector('[data-bs-target="' + hash + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
});
</script>
<?= $this->endSection() ?>
