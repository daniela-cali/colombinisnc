<?php

/**
 * @var array $utenti elenco account con username, status, email, gruppi, personale_id, cliente_id
 * @var array $gruppi mappa chiave => etichetta dei gruppi dell'applicazione
 */
$this->extend('layouts/admin');

// Smistamento fra le due schede. La regola è volutamente esaustiva: va fra i clienti chi ha
// un cliente collegato o appartiene al gruppo cliente, e in Personale finisce tutto il resto —
// compreso un eventuale account senza nessuna scheda. Nessun account può sparire da entrambe:
// questa pagina è l'unico posto da cui vedere chi ha accesso al gestionale.
$personale = [];
$clienti   = [];

foreach ($utenti as $u) {
    $suoiGruppi = $u['gruppi'] ? explode(', ', $u['gruppi']) : [];

    if ($u['cliente_id'] || in_array('cliente', $suoiGruppi, true)) {
        $clienti[] = $u;
    } else {
        $personale[] = $u;
    }
}
?>
<?= $this->section('title') ?>Utenti<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
        <li class="breadcrumb-item active">Utenti</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>Utenti
                </h3>
            </div>
            <div class="card-body p-0">

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-personale"
                                type="button" role="tab">
                            <i class="bi bi-person-badge me-1"></i>Personale
                            <span class="badge bg-secondary ms-1"><?= count($personale) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-clienti"
                                type="button" role="tab">
                            <i class="bi bi-person-vcard me-1"></i>Clienti
                            <span class="badge bg-secondary ms-1"><?= count($clienti) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-personale" role="tabpanel">
                        <?php $this->setData([
                            'righe'         => $personale,
                            'etichettaNome' => 'Nominativo',
                            'vuoto'         => 'Nessun account collegato a un dipendente.',
                            'id'            => 'tabella-utenti-personale',
                        ]); ?>
                        <?= $this->include('impostazioni/_tabella_utenti') ?>
                    </div>
                    <div class="tab-pane fade" id="pane-clienti" role="tabpanel">
                        <?php $this->setData([
                            'righe'         => $clienti,
                            'etichettaNome' => 'Cliente',
                            'vuoto'         => 'Nessun account cliente.',
                            'id'            => 'tabella-utenti-clienti',
                        ]); ?>
                        <?= $this->include('impostazioni/_tabella_utenti') ?>
                    </div>
                </div>

            </div>
            <div class="card-footer text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                Email, ruoli e password si modificano dalla scheda del dipendente in
                <a href="<?= base_url('anagrafiche/personale') ?>">Anagrafiche &rarr; Personale</a>;
                ognuno può cambiare i propri dal proprio profilo.
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script>
$(function () {
    // DataTables solo sulla tabella dei clienti: gli account del personale sono una manciata
    // e non hanno bisogno di ricerca e paginazione, quelli dei clienti sì.
    var $clienti = $('#tabella-utenti-clienti');

    if (! $clienti.length) {
        return;
    }

    var tabella = initTabella($clienti, {
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1], responsivePriority: 2 },
            { responsivePriority: 1, targets: 1 }
        ]
    });

    // La scheda Clienti è nascosta al caricamento: lì dentro ogni misura vale zero, quindi DT
    // fissa le larghezze delle colonne sbagliate e l'intestazione resta disallineata dal corpo.
    // Le si fa rifare appena il pannello è davvero visibile.
    var tab = document.querySelector('[data-bs-target="#pane-clienti"]');

    if (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            tabella.columns.adjust();
        });
    }
});
</script>
<?= $this->endSection() ?>
