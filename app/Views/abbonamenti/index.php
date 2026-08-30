<?php
/**
 * @var string $title
 * @var array  $abbonamenti  Da AbbonamentiModel::elencoConDettagli() — include stato_calcolato, successore_id, anno_inizio, num_periodi, prima_frequenza, più il flag rinnovabile aggiunto dal controller
 * @var array  $tipiPresenti Nomi distinti dei tipi intervento presenti in $abbonamenti, ordinati alfabeticamente
 * @var array  $anniPresenti Anni distinti (da data_inizio) presenti in $abbonamenti, ordinati decrescenti
 * @var array  $statiLabel   AbbonamentiModel::STATI_LABEL
 * @var array  $statiBadge   AbbonamentiModel::STATI_BADGE
 * @var array  $frequenze    AbbonamentiModel::FREQUENZE_LABEL
 */
$this->extend('layouts/admin');

/* Rango per l'ordinamento della colonna Stato: la cella mostra un badge, quindi senza
   data-order DataTables ordinerebbe per l'etichetta, cioè alfabeticamente (Attivo,
   Disdetto, Proposta...). L'ordine segue la vita del contratto — nasce proposta,
   diventa attivo, può sospendersi, poi scade — con in fondo le due uscite anticipate:
   disdetto (chiuso prima del tempo) e rifiutata (proposta mai accettata). */
$statoOrdine = [
    'proposta'  => 1,
    'attivo'    => 2,
    'sospeso'   => 3,
    'scaduto'   => 4,
    'disdetto'  => 5,
    'rifiutata' => 6,
];
?>
<?= $this->section('title') ?>Abbonamenti<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Abbonamenti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Abbonamenti
            <i class="bi bi-info-circle text-muted ms-2"
               style="font-size:.85rem; font-weight:normal"
               data-bs-toggle="tooltip"
               title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
        </h3>
        <div class="card-tools ms-auto">
            <button type="submit" form="form-accetta-multiplo" id="btn-accetta-multiplo"
                    class="btn btn-sm btn-success" disabled>
                <i class="bi bi-clipboard-check-fill me-1"></i>Accetta selezionati
            </button>
            <a href="<?= base_url('abbonamenti/nuovo') ?>" class="btn btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuovo
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($abbonamenti)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun abbonamento presente.</p>
        <?php else: ?>
            <?php
            // Tipo: sulla colonna 3 già visibile (Tipo intervento), una voce per tipo presente.
            $vociTipo = ['tutti' => ['label' => 'Tutti i tipi', 'default' => true]];
            foreach ($tipiPresenti as $tipo) {
                $vociTipo[$tipo] = ['label' => $tipo, 'col' => 3, 'q' => '^' . preg_quote($tipo, '/') . '$', 'regex' => true];
            }

            // Anno: sulla colonna nascosta 10 (anno_inizio), una voce per anno presente.
            $vociAnno = ['tutti' => ['label' => 'Tutti gli anni', 'default' => true]];
            foreach ($anniPresenti as $anno) {
                $vociAnno[$anno] = ['label' => $anno, 'col' => 10, 'q' => '^' . preg_quote($anno, '/') . '$', 'regex' => true];
            }
            ?>
            <div class="mb-3 d-flex flex-wrap gap-2">
                <?php /* Stato: tutto sulla colonna nascosta 9. "Scaduti" cerca per prefisso, senza
                         ancora di chiusura, così intercetta sia "scaduto con-rinnovo" sia
                         "scaduto senza-rinnovo", che restano disponibili come voci rientrate. */ ?>
                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Stato',
                    'classe'    => 'btn-outline-primary',
                    'voci'      => [
                        'tutti'                 => ['label' => 'Tutti (' . count($abbonamenti) . ')'],
                        'attivo'                => ['label' => 'Attivi', 'icona' => 'bi-check-circle', 'default' => true,
                                                    'col' => 9, 'q' => '^attivo$', 'regex' => true],
                        'sospeso'               => ['label' => 'Sospesi', 'icona' => 'bi-pause-circle',  'col' => 9, 'q' => '^sospeso$', 'regex' => true],
                        'scaduto'               => ['label' => 'Scaduti', 'icona' => 'bi-clock-history', 'col' => 9, 'q' => '^scaduto',  'regex' => true],
                        'scaduto_con_rinnovo'   => ['label' => 'con rinnovo',   'sotto' => true, 'col' => 9, 'q' => '^scaduto con-rinnovo$',   'regex' => true],
                        'scaduto_senza_rinnovo' => ['label' => 'senza rinnovo', 'sotto' => true, 'col' => 9, 'q' => '^scaduto senza-rinnovo$', 'regex' => true],
                        'disdetto'              => ['label' => 'Disdetti',  'icona' => 'bi-x-circle',          'col' => 9, 'q' => '^disdetto$',  'regex' => true],
                        'proposta'              => ['label' => 'Proposte',  'icona' => 'bi-file-earmark-text', 'col' => 9, 'q' => '^proposta$',  'regex' => true],
                        'rifiutata'             => ['label' => 'Rifiutati', 'icona' => 'bi-x-circle',          'col' => 9, 'q' => '^rifiutata$', 'regex' => true],
                    ],
                ]) ?>

                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Tipo',
                    'icona'     => 'bi-tag',
                    'attivo'    => 'Tutti',
                    'voci'      => $vociTipo,
                ]) ?>

                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Anno',
                    'icona'     => 'bi-calendar3',
                    'attivo'    => 'Tutti',
                    'voci'      => $vociAnno,
                ]) ?>
            </div>
            <form id="form-accetta-multiplo" method="post" action="<?= base_url('abbonamenti/accetta-multiplo') ?>"
                  onsubmit="return confirm('Accettare le proposte selezionate? Verranno generati gli interventi.')">
                <?= csrf_field() ?>
                <div class="table-responsive">
                    <table id="tabella-abbonamenti" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="check-tutti" title="Seleziona tutte le proposte"></th>
                                <th style="width:70px">Rif.</th>
                                <th>Cliente</th>
                                <th>Tipo intervento</th>
                                <th>Frequenza</th>
                                <th class="text-center">Periodo</th>
                                <th class="text-center">Prezzo</th>
                                <th class="text-center">Stato</th>
                                <th style="width:100px"></th>
                                <th></th><!-- 9 Filter stato_calcolato -->
                                <th></th><!-- 10 Filter anno_inizio -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abbonamenti as $a): ?>
                                <tr>
                                    <!-- 0 Checkbox selezione -->
                                    <td class="text-center">
                                        <?php if ($a['stato_calcolato'] === 'proposta'): ?>
                                            <input type="checkbox" name="ids[]" value="<?= (int) $a['id'] ?>" class="check-riga">
                                        <?php endif ?>
                                    </td>
                                    <!-- 1 Rif. -->
                                    <td data-order="<?= (int) $a['id'] ?>">
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-decoration-none js-row-open">
                                            <code class="small">#<?= (int) $a['id'] ?></code>
                                        </a>
                                    </td>
                                    <!-- 2 Cliente -->
                                    <td>
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-body text-decoration-none">
                                            <?= esc($a['cliente_denominazione']) ?>
                                        </a>
                                    </td>
                                    <!-- 3 Tipo intervento -->
                                    <td><?= esc($a['tipo_nome'] ?? '—') ?></td>
                                    <!-- 4 Frequenza -->
                                    <td>
                                        <?php if ($a['num_periodi'] > 1): ?>
                                            Multipla
                                            <small class="text-muted">(<?= (int) $a['num_periodi'] ?> periodi)</small>
                                        <?php else: ?>
                                            <?= esc($frequenze[$a['prima_frequenza']] ?? '—') ?>
                                        <?php endif ?>
                                    </td>
                                    <!-- 5 Periodo -->
                                    <td class="text-center text-nowrap" data-order="<?= esc($a['data_inizio']) ?>">
                                        <?= date('d/m/Y', strtotime($a['data_inizio'])) ?>
                                        –
                                        <?= date('d/m/Y', strtotime($a['data_fine'])) ?>
                                    </td>
                                    <!-- 6 Prezzo -->
                                    <td class="text-end text-nowrap" data-order="<?= $a['prezzo'] !== null ? (float) $a['prezzo'] : -1 ?>">
                                        <?= $a['prezzo'] !== null ? '€ ' . number_format((float) $a['prezzo'], 2, ',', '.') : '—' ?>
                                    </td>
                                    <!-- 7 Stato -->
                                    <td class="text-center" data-order="<?= $statoOrdine[$a['stato_calcolato']] ?? 99 ?>">
                                        <span class="badge <?= $statiBadge[$a['stato_calcolato']] ?? 'bg-secondary' ?>">
                                            <?= esc($statiLabel[$a['stato_calcolato']] ?? $a['stato_calcolato']) ?>
                                        </span>
                                    </td>
                                    <!-- 8 Azioni -->
                                    <td class="text-end text-nowrap">
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>"
                                           class="btn btn-sm btn-outline-secondary" title="Scheda">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($a['successore_id']): ?>
                                            <a href="<?= base_url('abbonamenti/' . $a['successore_id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Vai al rinnovo">
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </a>
                                        <?php elseif ($a['rinnovabile']): ?>
                                            <a href="<?= base_url('abbonamenti/' . $a['id'] . '/rinnova') ?>"
                                               class="btn btn-sm btn-outline-primary" title="Rinnova">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </a>
                                        <?php endif ?>
                                        <?php if (in_array($a['stato_calcolato'], ['proposta'], true)): ?>
                                            <form action="<?= base_url('abbonamenti/' . $a['id'] . '/accetta') ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Accetta"
                                                        onclick="return confirm('Accettare la proposta? Verranno generati gli interventi.')">
                                                    <i class="bi bi-clipboard-check-fill"></i>
                                                </button>
                                            </form>
                                            <form action="<?= base_url('abbonamenti/' . $a['id'] . '/rifiuta') ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Rifiuta"
                                                        onclick="return confirm('Rifiutare questa proposta?')">
                                                    <i class="bi bi-clipboard-x-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif ?>
                                    </td>
                                    <!-- 9 Filter stato_calcolato -->
                                    <td><?= esc($a['stato_calcolato']) ?><?= $a['stato_calcolato'] === 'scaduto' ? ($a['successore_id'] ? ' con-rinnovo' : ' senza-rinnovo') : '' ?></td>
                                    <!-- 10 Filter anno_inizio -->
                                    <td><?= esc($a['anno_inizio']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script src="<?= base_url('js/search-bar.js') ?>"></script>
<script>
$(function () {

    var table = new DataTable('#tabella-abbonamenti', {
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
        order:       [[1, 'desc']],
        columnDefs: [
            { name: 'select',    targets: 0, searchable: false, orderable: false, responsivePriority: 3 },
            { name: 'rif',       targets: 1, searchable: false, responsivePriority: 2 },
            { name: 'cliente',   targets: 2, responsivePriority: 1 },
            { name: 'tipo',      targets: 3 },
            { name: 'frequenza', targets: 4 },
            { name: 'periodo',   targets: 5, searchable: false },
            { name: 'prezzo',    targets: 6, searchable: false },
            // Ordina per il rango del ciclo di vita (data-order sulla cella), non per l'etichetta del badge.
            // Resta non cercabile: lo stato grezzo è già nella colonna nascosta 9.
            { name: 'stato',     targets: 7, searchable: false, responsivePriority: 3 },
            { name: 'azioni',    targets: 8, searchable: false, orderable: false, responsivePriority: 2 },
            { name: 'filter_stato', targets: 9, searchable: true, orderable: false, visible: false },
            { name: 'filter_anno',  targets: 10, searchable: true, orderable: false, visible: false }
        ]
    });

    // Filtri iniziali: quelli ricordati dalla sessione, altrimenti i default — vedi search-bar.js
    filtriIniziali('tabella-abbonamenti');

    // Selezione multipla proposte: "seleziona tutte" + abilitazione bottone "Accetta selezionati"
    function aggiornaBottoneAccetta() {
        var selezionate = document.querySelectorAll('.check-riga:checked').length;
        document.getElementById('btn-accetta-multiplo').disabled = selezionate === 0;
    }

    document.getElementById('check-tutti').addEventListener('change', function () {
        document.querySelectorAll('.check-riga').forEach(function (cb) { cb.checked = this.checked; }, this);
        aggiornaBottoneAccetta();
    });

    document.getElementById('tabella-abbonamenti').addEventListener('change', function (e) {
        if (e.target.classList.contains('check-riga')) {
            aggiornaBottoneAccetta();
        }
    });
});
</script>
<?= $this->endSection() ?>
