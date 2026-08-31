<?php
/**
 * @var string $title
 * @var array  $cantieri    Da CantieriModel::elencoCompleto() — include cliente_denominazione
 * @var array  $tipiLabel   CantieriModel::TIPI_LABEL
 * @var array  $statiLabel  CantieriModel::STATI_LABEL
 * @var array  $statiBadge  CantieriModel::STATI_BADGE
 */
$this->extend('layouts/admin');

/* Rango per l'ordinamento della colonna Stato: la cella mostra un badge, quindi senza
   data-order DataTables ordinerebbe per l'etichetta, cioè in ordine alfabetico
   (Aperto, Chiuso, Sospeso). Così invece segue la vita del cantiere. */
$statoOrdine = [
    'aperto'  => 1,
    'sospeso' => 2,
    'chiuso'  => 3,
];

/* Anni attraversati da un cantiere, per la tendina Anno: un cantiere ha una durata, quindi
   uno iniziato a novembre 2025 e finito a marzo 2026 deve uscire sia da "2025" sia da "2026".
   La colonna nascosta contiene quindi più anni per riga ("2025 2026") e il filtro ne cerca uno,
   stesso meccanismo dei secchielli di periodo negli interventi.
   Senza data di fine prevista si arriva fino all'anno corrente: un cantiere aperto nel 2024 e
   mai chiuso è ancora lavoro di oggi. Senza data di inizio non c'è nessun anno da attribuire. */
$annoCorrente = (int) date('Y');

$anniDi = static function (array $c) use ($annoCorrente): array {
    if (empty($c['data_inizio'])) {
        return [];
    }

    $da = (int) substr($c['data_inizio'], 0, 4);
    $a  = ! empty($c['data_fine_prevista'])
        ? (int) substr($c['data_fine_prevista'], 0, 4)
        : max($da, $annoCorrente);

    /* Il max() vale per i cantieri salvati prima della regola non_anteriore_a: da ora
       la fine anteriore all'inizio non passa più la validazione, ma i dati già a
       database possono averla, e range() rovesciato elencherebbe anni a caso. */
    return range($da, max($da, $a));
};

$anniPresenti = [];
foreach ($cantieri as $c) {
    $anniPresenti = array_merge($anniPresenti, $anniDi($c));
}
$anniPresenti = array_unique($anniPresenti);
rsort($anniPresenti);
?>
<?= $this->section('title') ?>Cantieri<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Cantieri</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-bricks me-2"></i>Cantieri
            <i class="bi bi-info-circle text-muted ms-2"
               style="font-size:.85rem; font-weight:normal"
               data-bs-toggle="tooltip"
               title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
        </h3>
        <div class="card-tools ms-auto">
            <a href="<?= base_url('cantieri/nuovo') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nuovo cantiere
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($cantieri)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun cantiere presente.</p>
        <?php else: ?>
            <?php
            /* Stato sulla colonna nascosta 7, anno sulla 8. Le due tendine sono gruppi
               indipendenti: search-bar.js azzera solo le colonne del proprio gruppo,
               quindi i filtri si combinano (es. i cantieri aperti del 2025).
               La colonna 8 contiene più anni per riga ("2025 2026"), quindi si cerca la
               singola parola con \b e non l'intero contenuto con ^...$. */
            $vociAnno = ['tutti' => ['label' => 'Tutti gli anni', 'default' => true]];
            foreach ($anniPresenti as $anno) {
                $vociAnno[$anno] = ['label' => $anno, 'col' => 8, 'q' => '\\b' . $anno . '\\b', 'regex' => true];
            }
            ?>
            <div class="mb-3 d-flex flex-wrap gap-2">
                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-cantieri',
                    'etichetta' => 'Stato',
                    'classe'    => 'btn-outline-primary',
                    'voci'      => [
                        'tutti'   => ['label' => 'Tutti (' . count($cantieri) . ')'],
                        'aperto'  => ['label' => 'Aperti', 'icona' => 'bi-unlock', 'default' => true,
                                      'col' => 7, 'q' => '^aperto$', 'regex' => true],
                        'sospeso' => ['label' => 'Sospesi', 'icona' => 'bi-pause-circle', 'col' => 7, 'q' => '^sospeso$', 'regex' => true],
                        'chiuso'  => ['label' => 'Chiusi',  'icona' => 'bi-lock',         'col' => 7, 'q' => '^chiuso$',  'regex' => true],
                    ],
                ]) ?>

                <?php if ($anniPresenti): ?>
                    <?= view('partials/filtro_tendina', [
                        'tabella'   => 'tabella-cantieri',
                        'etichetta' => 'Anno',
                        'icona'     => 'bi-calendar3',
                        'attivo'    => 'Tutti',
                        'voci'      => $vociAnno,
                    ]) ?>
                <?php endif ?>
            </div>
            <div class="table-responsive">
                <table id="tabella-cantieri" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px">Rif.</th>
                            <th>Cliente</th>
                            <th>Titolo</th>
                            <th>Tipo</th>
                            <th class="text-center">Periodo</th>
                            <th class="text-center">Stato</th>
                            <th style="width:60px"></th>
                            <th></th><!-- 7 Filter stato -->
                            <th></th><!-- 8 Filter anni attraversati -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cantieri as $c): ?>
                            <tr>
                                <!-- 1 Rif. -->
                                <td data-order="<?= (int) $c['id'] ?>">
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>" class="text-decoration-none js-row-open">
                                        <code class="small">#<?= (int) $c['id'] ?></code>
                                    </a>
                                </td>
                                <!-- 2 Cliente -->
                                <td>
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>" class="text-body text-decoration-none">
                                        <?= esc($c['cliente_denominazione']) ?>
                                    </a>
                                </td>
                                <!-- 3 Titolo -->
                                <td><?= esc($c['titolo']) ?></td>
                                <!-- 4 Tipo -->
                                <td>
                                    <?= esc($tipiLabel[$c['tipo']] ?? $c['tipo']) ?>
                                    <?php if (! empty($c['note'])): ?>
                                        <br><small class="text-muted"><?= esc(mb_strimwidth($c['note'], 0, 60, '…')) ?></small>
                                    <?php endif ?>
                                </td>
                                <!-- 5 Periodo -->
                                <td class="text-center text-nowrap" data-order="<?= esc($c['data_inizio'] ?? '') ?>">
                                    <?= $c['data_inizio'] ? date('d/m/Y', strtotime($c['data_inizio'])) : '—' ?>
                                    <?php if ($c['data_fine_prevista']): ?>
                                        – <?= date('d/m/Y', strtotime($c['data_fine_prevista'])) ?>
                                    <?php endif ?>
                                </td>
                                <!-- 6 Stato -->
                                <td class="text-center" data-order="<?= $statoOrdine[$c['stato']] ?? 99 ?>">
                                    <span class="badge <?= $statiBadge[$c['stato']] ?? 'bg-secondary' ?>">
                                        <?= esc($statiLabel[$c['stato']] ?? $c['stato']) ?>
                                    </span>
                                </td>
                                <!-- 7 Azioni -->
                                <td class="text-end">
                                    <a href="<?= base_url('cantieri/' . $c['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Scheda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <!-- 8 Filter stato -->
                                <td><?= esc($c['stato']) ?></td>
                                <!-- 9 Filter anni attraversati -->
                                <td><?= implode(' ', $anniDi($c)) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script src="<?= base_url('js/search-bar.js') ?>"></script>
<script>
$(function () {

    var table = initTabella('#tabella-cantieri', {
        order: [[0, 'desc']],
        columnDefs: [
            { name: 'rif',      targets: 0, searchable: false, responsivePriority: 2 },
            { name: 'cliente',  targets: 1, responsivePriority: 1 },
            { name: 'titolo',   targets: 2 },
            { name: 'tipo',     targets: 3 },
            { name: 'periodo',  targets: 4, searchable: false },
            // Ordina per il rango del ciclo di vita (data-order sulla cella), non per l'etichetta del badge.
            // Resta non cercabile: lo stato grezzo è già nella colonna nascosta 7.
            { name: 'stato',    targets: 5, searchable: false, responsivePriority: 3 },
            { name: 'azioni',   targets: 6, searchable: false, orderable: false, responsivePriority: 2 },
            { name: 'filter_stato', targets: 7, searchable: true, orderable: false, visible: false },
            { name: 'filter_anno',  targets: 8, searchable: true, orderable: false, visible: false }
        ]
    });

    // Filtri iniziali: quelli ricordati dalla sessione, altrimenti i default — vedi search-bar.js
    filtriIniziali('tabella-cantieri');
});
</script>
<?= $this->endSection() ?>
