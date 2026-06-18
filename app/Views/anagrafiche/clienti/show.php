<?php
/**
 * @var array $cliente        Record clienti con tutti i campi
 * @var array $interventi     Righe da InterventiModel::perCliente()
 * @var array $materiali      Righe da InterventiMaterialiModel::perCliente()
 * @var array $sospesi        Righe da InterventiMaterialiModel::sospesiPerCliente()
 * @var array $articoliPerCat Da ArticoliModel::perCategoria()
 * @var array $prioritaLabel  Map priorita → label leggibile
 * @var array $statiLabel     Map stato  → label leggibile
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
<link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/rowGroup.bootstrap5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/tom-select/tom-select.bootstrap5.min.css') ?>">
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
                                            <td>
                                                <a href="<?= base_url('operativo/interventi/' . $iv['id']) ?>"
                                                   class="text-decoration-none">
                                                    <code class="small"><?= esc($iv['codice']) ?></code>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($iv['tipo_intervento_icona']): ?>
                                                    <i class="fas <?= esc($iv['tipo_intervento_icona']) ?> me-1 text-muted"></i>
                                                <?php endif ?>
                                                <?= esc($iv['tipo_intervento_nome'] ?? '—') ?>
                                            </td>
                                            <td class="text-muted small"><?= esc($prioritaLabel[$iv['priorita']] ?? $iv['priorita']) ?></td>
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

                    <!-- Materiali sospesi (non ancora legati a un intervento) -->
                    <div class="card-body border-bottom pb-4">
                        <p class="text-muted section-header mb-3">
                            <i class="bi bi-box-seam me-1"></i> Materiali da portare
                            <?php if (! empty($sospesi)): ?>
                                <span class="badge bg-warning text-dark ms-1"><?= count($sospesi) ?></span>
                            <?php endif ?>
                        </p>

                        <?php if (! empty($sospesi)): ?>
                            <table class="table table-sm align-middle mb-4">
                                <thead class="table-light">
                                    <tr>
                                        <th>Articolo / Descrizione</th>
                                        <th class="text-center" style="width:60px">Qtà</th>
                                        <th>Note</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sospesi as $s): ?>
                                        <tr>
                                            <td><?= esc($s['desc_materiale']) ?></td>
                                            <td class="text-center"><?= (int) $s['quantita'] ?></td>
                                            <td class="text-muted small"><?= esc($s['note'] ?? '') ?></td>
                                            <td>
                                                <form action="<?= base_url('operativo/materiali/' . $s['id'] . '/delete') ?>"
                                                      method="post" class="d-inline"
                                                      onsubmit="return confirm('Eliminare questo materiale?')">
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
                        <?php else: ?>
                            <p class="text-muted small mb-3">Nessun materiale in attesa.</p>
                        <?php endif ?>

                        <!-- Mini-form aggiunta sospeso -->
                        <form action="<?= base_url('operativo/materiali/store') ?>" method="post" id="form-sospeso">
                            <?= csrf_field() ?>
                            <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                            <input type="hidden" name="articolo_id" id="hs-articolo-id">
                            <input type="hidden" name="descrizione"  id="hs-descrizione">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small">Articolo / Descrizione <span class="text-danger">*</span></label>
                                    <select id="sel-sospeso" placeholder="Cerca articolo o digita descrizione libera…">
                                        <option value=""></option>
                                        <?php foreach ($articoliPerCat as $cat): ?>
                                            <optgroup label="<?= esc($cat['nome']) ?>">
                                                <?php foreach ($cat['articoli'] as $a): ?>
                                                    <option value="<?= $a['id'] ?>"><?= esc($a['descrizione']) ?></option>
                                                <?php endforeach ?>
                                            </optgroup>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Qtà</label>
                                    <input type="number" name="quantita" class="form-control form-control-sm" min="1" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Note</label>
                                    <input type="text" name="note" class="form-control form-control-sm" maxlength="255">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-plus-lg me-1"></i>Aggiungi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Storico materiali per intervento (rowGroup) -->
                    <?php if (empty($materiali)): ?>
                        <div class="card-body text-center py-4 text-muted">
                            <p class="mb-0 small">Nessun materiale ancora associato a un intervento.</p>
                        </div>
                    <?php else: ?>
                        <table id="tbl-materiali" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th></th><!-- data ISO, hidden -->
                                    <th></th><!-- gruppo label, hidden -->
                                    <th></th><!-- intervento_id, hidden -->
                                    <th>Articolo / Descrizione</th>
                                    <th class="text-center" style="width:70px">Qtà</th>
                                    <th class="text-center" style="width:110px">Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materiali as $m): ?>
                                    <tr>
                                        <td><?= esc($m['data_pianificata'] ?? '') ?></td>
                                        <td><?= esc(($m['data_pianificata'] ? date('d/m/Y', strtotime($m['data_pianificata'])) : 'Da pianificare') . ' — ' . $m['codice_intervento']) ?></td>
                                        <td><?= (int) $m['intervento_id_ref'] ?></td>
                                        <td><?= esc($m['desc_materiale']) ?></td>
                                        <td class="text-center"><?= (int) $m['quantita'] ?></td>
                                        <td class="text-center">
                                            <?php if ($m['stato'] === \App\Models\InterventiMaterialiModel::STATO_CONSEGNATO): ?>
                                                <span class="badge bg-success">Consegnato</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Da portare</span>
                                            <?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    <?php endif ?>
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
<script src="<?= base_url('assets/vendor/tom-select/tom-select.complete.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.rowGroup.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/rowGroup.bootstrap5.min.js') ?>"></script>
<script>
// Tom Select — form materiali sospesi
(function () {
    var ts = new TomSelect('#sel-sospeso', {
        wrapperClass: 'ts-wrapper ts-upper',
        create: function (input) {
            var v = input.trim().toUpperCase();
            return { value: v, text: v };
        },
        createOnBlur: true,
        placeholder: 'Cerca articolo o digita descrizione libera…',
        allowEmptyOption: true,
        createFilter: function (input) { return input.trim().length > 0; }
    });

    document.getElementById('form-sospeso').addEventListener('submit', function (e) {
        var val = ts.getValue();
        if (! val) { e.preventDefault(); alert('Seleziona un articolo o digita una descrizione.'); return; }
        if (/^\d+$/.test(val)) {
            document.getElementById('hs-articolo-id').value = val;
            document.getElementById('hs-descrizione').value  = '';
        } else {
            document.getElementById('hs-articolo-id').value = '';
            document.getElementById('hs-descrizione').value  = val;
        }
    });
})();

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

    // Tab Materiali — DataTables con rowGroup per intervento
    if (document.getElementById('tbl-materiali')) {
        var urlIntervento = '<?= base_url('operativo/interventi/') ?>';
        var tblMat = $('#tbl-materiali').DataTable({
            order: [[0, 'desc']],
            rowGroup: {
                dataSrc: 1,
                startRender: function (rows, group) {
                    var id = rows.data()[0][2];
                    return $('<tr/>').append(
                        $('<td/>').attr('colspan', 3).html(
                            '<strong>' + group + '</strong>'
                            + ' <a href="' + urlIntervento + id + '" class="ms-2 text-muted small">'
                            + '<i class="bi bi-arrow-right-circle"></i></a>'
                        )
                    );
                }
            },
            columnDefs: [
                { visible: false, targets: [0, 1, 2] },
                { orderable: false, targets: '_all' }
            ],
            paging:    false,
            searching: false,
            info:      false,
            language:  { emptyTable: 'Nessun materiale consegnato.' }
        });

        document.getElementById('tab-materiali').addEventListener('shown.bs.tab', function () {
            tblMat.columns.adjust().draw(false);
        });
    }

    // Attiva il tab corrispondente all'anchor dell'URL (usato dal sistema ?from=)
    var hash = location.hash;
    if (hash) {
        var trigger = document.querySelector('[data-bs-target="' + hash + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
});
</script>
<?= $this->endSection() ?>
