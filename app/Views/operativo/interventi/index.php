<?php
/**
 * @var array  $interventi    Righe da InterventiModel::elencoCompleto($sezione)
 * @var array  $prioritaLabel [codice => etichetta]
 * @var array  $statiLabel    [codice => etichetta]
 * @var string $sezione       Categoria corrente: generale | piscine | addolcitori
 * @var string $sezioneLabel  Etichetta della sezione (titolo pagina)
 */
$this->extend('layouts/admin');

$statoBadge = [
    'da_pianificare' => 'secondary',
    'pianificato'    => 'primary',
    'in_corso'       => 'warning text-dark',
    'completato'     => 'success',
    'annullato'      => 'danger',
];
$prioritaBadge = [
    'abbonamento' => 'primary',
    'normale'     => 'secondary',
    'urgente'     => 'danger',
];
?>
<?= $this->section('title') ?><?= esc($sezioneLabel) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active"><?= esc($sezioneLabel) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i><?= esc($sezioneLabel) ?>
                    <i class="bi bi-info-circle text-muted ms-2"
                       style="font-size:.85rem; font-weight:normal"
                       data-bs-toggle="tooltip"
                       title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('operativo/interventi/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3 filtri-scroll">
                    <button class="btn btn-sm btn-outline-warning" data-filtro="da_pianificare">
                        <i class="bi bi-hourglass-split me-1"></i>Da pianificare
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-filtro="pianificati">
                        <i class="bi bi-calendar-check me-1"></i>Pianificati
                    </button>
                    <button class="btn btn-sm btn-outline-success" data-filtro="completati">
                        <i class="bi bi-check-circle me-1"></i>Completati
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-filtro="annullati">
                        <i class="bi bi-x-circle me-1"></i>Annullati
                    </button>
                    <?php if (in_array($sezione, ['piscine', 'addolcitori'], true)): ?>
                        <button class="btn btn-sm btn-outline-info" data-filtro="abbonamento">
                            <i class="bi bi-file-earmark-text me-1"></i>Abbonamenti
                        </button>
                    <?php endif ?>
                    <?php if ($sezione === 'piscine'): ?>
                        <button class="btn btn-sm btn-outline-info" data-filtro="aperture">
                            <i class="bi bi-box-arrow-up me-1"></i>Aperture
                        </button>
                        <button class="btn btn-sm btn-outline-info" data-filtro="chiusure">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Chiusure
                        </button>
                    <?php endif ?>
                    <button class="btn btn-sm btn-outline-secondary" data-filtro="tutti">
                        Tutti (<?= count($interventi) ?>)
                    </button>
                </div>
                <?php if (empty($interventi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun intervento trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabella-interventi" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Codice</th> 
                                    <th>Cliente</th> 
                                    <th>Tipo</th> 
                                    <th>Stato</th> 
                                    <th class="text-center">Data pianificata</th> 
                                    <th class="text-center">Data scadenza</th> 
                                    <th>Tecnico</th> 
                                    <th class="text-center">Urg.</th> 
                                    <th></th>
                                    <th></th><!-- 9 Filter stato raw -->
                                    <th></th><!-- 10 Filter origine: abbonamento|singolo -->
                                    <th></th><!-- 11 Filter fase: apertura|chiusura|'' -->
                                </tr>                      
                            </thead>
                            <tbody>
                                <?php foreach ($interventi as $i): ?>
                                    <tr class="<?= $i['urgenza'] ? 'table-danger' : '' ?>" title="ID intervento: <?= $i['id'] ?>"><?php // ID utile per debug DB ?>
                                        <!-- 1 Codice -->
                                        <td> 
                                            <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>"
                                               class="text-decoration-none js-row-open">
                                                <code class="small"><?= esc($i['codice']) ?></code>
                                            </a>
                                        </td>
                                        <!-- 2 Cliente -->
                                        <td title="ID cliente: <?= $i['cliente_id'] ?>"> 
                                            <a href="<?= base_url('anagrafiche/clienti/' . $i['cliente_id']) ?>"
                                               class="text-body text-decoration-none">
                                                <?= esc($i['cliente_denominazione']) ?>
                                            </a>
                                        </td>
                                        <!-- 3 Tipo -->
                                        <td>
                                            <?php if ($i['tipo_intervento_nome']): ?>
                                                <?php if ($i['tipo_intervento_icona']): ?>
                                                    <i class="fas <?= esc($i['tipo_intervento_icona']) ?> text-muted me-1 small"></i>
                                                <?php endif ?>
                                                <span class="small"><?= esc($i['tipo_intervento_nome']) ?></span>
                                                <br>
                                            <?php endif ?>
                                            <span class="badge bg-<?= $prioritaBadge[$i['priorita']] ?? 'secondary' ?>">
                                                <?= esc($prioritaLabel[$i['priorita']] ?? $i['priorita']) ?>
                                            </span>
                                            <?php if ($i['extra']): ?>
                                                <span class="badge bg-warning text-dark ms-2">Extra</span>
                                            <?php endif ?>
                                            <?php if (! empty($i['apertura'])): ?>
                                                <span class="badge bg-info text-dark ms-2"><i class="bi bi-box-arrow-up me-1"></i>Apertura</span>
                                            <?php elseif (! empty($i['chiusura'])): ?>
                                                <span class="badge bg-info text-dark ms-2"><i class="bi bi-box-arrow-in-down me-1"></i>Chiusura</span>
                                            <?php endif ?>
                                        </td>
                                        <!-- 4 Stato-->
                                        <td>
                                            <span class="badge bg-<?= $statoBadge[$i['stato']] ?? 'secondary' ?>">
                                                <?= esc($statiLabel[$i['stato']] ?? $i['stato']) ?>
                                            </span>
                                        </td>
                                        <!-- 5 Data pianificata -->
                                        <td data-order="<?= esc($i['data_pianificata'] ?? '') ?>">
                                            <?= $i['data_pianificata']
                                                ? esc(date('d/m/Y H:i', strtotime($i['data_pianificata'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <!-- 6 Scadenza-->
                                        <td data-order="<?= esc($i['data_scadenza'] ?? '') ?>">
                                            <?= $i['data_scadenza']
                                                ? esc(date('d/m/Y', strtotime($i['data_scadenza'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <!-- 7 Tecnico -->
                                        <td class="text-muted small"><?= esc($i['tecnico_nome'] ?? '—') ?></td>
                                        <!-- 8 Urgenza -->
                                        <td class="text-center">
                                            <?php if ($i['urgenza']): ?>
                                                <i class="bi bi-exclamation-triangle-fill text-danger"
                                                   data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                                            <?php endif ?>
                                        </td>
                                        <!-- 9 Edit-->
                                        <td class="text-end">
                                            <a href="<?= base_url('operativo/interventi/' . $i['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                        <!-- 10 Filter stato raw -->
                                        <td><?= esc($i['stato']) ?></td>
                                        <!-- 11 Filter origine: abbonamento|singolo -->
                                        <td><?= ($i['abbonamento_id'] && empty($i['extra'])) ? 'abbonamento' : 'singolo' ?></td>
                                        <!-- 12 Filter fase: apertura|chiusura|'' -->
                                        <td><?= ! empty($i['apertura']) ? 'apertura' : (! empty($i['chiusura']) ? 'chiusura' : '') ?></td>
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

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script>
$(function () {

    var table = new DataTable('#tabella-interventi', {
        language: {
            search:       'Cerca:',
            lengthMenu:   'Mostra _MENU_ righe',
            info:         'Da _START_ a _END_ di _TOTAL_ record',
            infoEmpty:    'Nessun record',
            infoFiltered: '(filtrati da _MAX_ totali)',
            zeroRecords:  'Nessun risultato trovato',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        },
        responsive: true,
        orderMulti: true, // già attivo di default in DataTables (Shift+clic ordina su più colonne)
        pageLength:  25,
        order:       [[4, 'desc']],
        columnDefs: [
        { name: 'codice', targets: 0 },
        { name: 'cliente', targets: 1, responsivePriority: 1 },
        { name: 'tipo', targets: 2 },
        { name: 'stato', targets: 3, searchable: false, orderable: false, responsivePriority: 3 },
        { name: 'dt_pianificata', targets: 4, searchable: false, responsivePriority: 4 },
        { name: 'dt_scadenza', targets: 5, searchable: false },
        { name: 'tecnico', targets: 6 },
        { name: 'urgenza', targets: 7 },
        { name: 'edit', targets: 8, searchable: false, orderable: false, responsivePriority: 2 },
        { 
            name: 'filter_stato', 
            targets: 9, 
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_origine',
            targets: 10,
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_fase',
            targets: 11,
            searchable: true,
            orderable: false,
            visible: false
        }

        ],
        initComplete: function () {
            this.api()
                .columns()
                .every(function () {
                    let column = this;
                    /* Escludo tutte quelle non searchable, stato è già filtrato dai pills */
                    if (!this.settings()[0].aoColumns[this.index()].bSearchable) return;


                    let input = document.createElement('input');
                    input.placeholder = column.title();
                    column.header().appendChild(input);

                    // Evita che il click sull'input faccia scattare l'ordinamento della colonna
                    input.addEventListener('click', (e) => e.stopPropagation());

                    input.addEventListener('keyup', () => {
                        column.search(input.value).draw();
                    });
                });
        }
    });

    // q  → colonna 9  (stato), q10 → colonna 10 (origine), q11 → colonna 11 (fase apertura/chiusura)
    var filtri = {
        da_pianificare: { q: '^da_pianificare$',         regex: true,  q10: '^singolo$',    q11: '' },
        pianificati:    { q: '^(pianificato|in_corso)$', regex: true,  q10: '',             q11: '' },
        completati:     { q: '^completato$',             regex: true,  q10: '',             q11: '' },
        annullati:      { q: '^annullato$',              regex: true,  q10: '',             q11: '' },
        abbonamento:    { q: '',                         regex: false, q10: '^abbonamento$', q11: '' },
        aperture:       { q: '',                         regex: false, q10: '',             q11: '^apertura$' },
        chiusure:       { q: '',                         regex: false, q10: '',             q11: '^chiusura$' },
        tutti:          { q: '',                         regex: false, q10: '',             q11: '' }
    };

    function setFiltro(nome) {
        var f = filtri[nome];
        table.column(9).search(f.q,   f.regex,        false);
        table.column(10).search(f.q10, f.q10 !== '',  false);
        table.column(11).search(f.q11, f.q11 !== '',  false);
        table.draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('da_pianificare');
});
</script>
<?= $this->endSection() ?>
