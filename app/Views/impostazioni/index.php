<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Impostazioni<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item active">Impostazioni</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">

    <div class="col-12 mt-3 mb-2">
        <p class="text-muted section-header">
            <i class="bi bi-gear me-1"></i> Configurazione generale
        </p>
    </div>

    <div class="col-md-4 mb-4">
        <a href="<?= base_url('impostazioni/parametri') ?>" class="text-decoration-none">
            <div class="card card-outline card-primary h-100 settings-card">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="bi bi-sliders settings-icon"></i>
                    <h5 class="card-title mt-3">Parametri Generali</h5>
                    <p class="text-muted small mb-0">
                        Dati azienda, sede, orari e logo.
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 mb-4">
        <a href="<?= base_url('impostazioni/import-clienti') ?>" class="text-decoration-none">
            <div class="card card-outline card-primary h-100 settings-card">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="bi bi-database-down settings-icon"></i>
                    <h5 class="card-title mt-3">Import Clienti</h5>
                    <p class="text-muted small mb-0">
                        Carica l'anagrafica storica e creane i clienti uno alla volta.
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card card-outline card-secondary h-100 settings-card">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4 text-muted">
                <i class="bi bi-envelope settings-icon text-muted"></i>
                <h5 class="card-title mt-3">Notifiche Email</h5>
                <p class="small mb-0">In costruzione.</p>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3 mb-2">
        <p class="text-muted section-header">
            <i class="bi bi-tools me-1"></i> Operativo
        </p>
    </div>

    <div class="col-md-4 mb-4">
        <a href="<?= base_url('impostazioni/tipi-intervento') ?>" class="text-decoration-none">
            <div class="card card-outline card-primary h-100 settings-card">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="bi bi-list-check settings-icon"></i>
                    <h5 class="card-title mt-3">Tipi Intervento</h5>
                    <p class="text-muted small mb-0">
                        Categorie di lavoro (piscine, filtri, addolcitori…) con durata standard.
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 mb-4">
        <a href="<?= base_url('impostazioni/categorie-articoli') ?>" class="text-decoration-none">
            <div class="card card-outline card-primary h-100 settings-card">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="bi bi-tags settings-icon"></i>
                    <h5 class="card-title mt-3">Categorie Articoli</h5>
                    <p class="text-muted small mb-0">
                        Raggruppamenti del catalogo (Prodotti, Attrezzature, Apparecchiature, Ricambi…).
                    </p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 mt-3 mb-2">
        <p class="text-muted section-header">
            <i class="bi bi-people me-1"></i> Utenti
        </p>
    </div>

    <div class="col-md-4 mb-4">
        <a href="<?= base_url('impostazioni/utenti-app') ?>" class="text-decoration-none">
            <div class="card card-outline card-primary h-100 settings-card">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="bi bi-person-gear settings-icon"></i>
                    <h5 class="card-title mt-3">Utenti</h5>
                    <p class="text-muted small mb-0">
                        Chi ha accesso alla piattaforma, personale e clienti, con i suoi ruoli.
                    </p>
                </div>
            </div>
        </a>
    </div>

</div>
<?= $this->endSection() ?>
