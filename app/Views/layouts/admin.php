<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> — Colombini SNC</title>

    <!-- Dark mode init: prima del CSS per evitare flash di tema sbagliato -->
    <script>
    (() => {
        'use strict';
        const key = 'lte-theme';
        let stored = null;
        try { stored = localStorage.getItem(key); } catch {}
        const prefersDark = globalThis.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = (stored === 'dark' || stored === 'light') ? stored : (prefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.style.colorScheme = theme;
    })();
    </script>

    <link rel="stylesheet" href="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/adminlte/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/custom.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<?php
helper('acl');
$_isDevMode      = (bool) array_intersect(['developer'], auth()->user()->getGroups());
$_cl             = changelog_data($_isDevMode);
$_versioneUtente = auth()->user()->ultima_versione_vista ?? '';
$_mostraNovita   = $_cl['versioneCorrente'] !== '' && $_versioneUtente !== $_cl['versioneCorrente'];
$_soloTecnico    = is_solo_tecnico();
$_helpFile       = (isset($help_sezione) && is_file(APPPATH . 'Views/help/' . $help_sezione . '.php')) ? $help_sezione : null;
?>
<script>
    // Coda per i modal auto-aperti all'accesso (novità, promemoria, ecc.):
    // li mostra uno alla volta invece che tutti insieme, evitando sovrapposizioni.
    (function () {
        var queue   = [];
        var running = false;

        function runNext() {
            if (queue.length === 0) { running = false; return; }
            running = true;
            var next = queue.shift();
            next.show();
            document.getElementById(next.id).addEventListener('hidden.bs.modal', runNext, { once: true });
        }

        window.enqueueModal = function (id, show) {
            queue.push({ id: id, show: show });
            if (!running) runNext();
        };
    })();
</script>
<div class="app-wrapper">

    <!-- Navbar -->
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($_helpFile): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalHelp" title="Guida di questa sezione">
                        <i class="bi bi-question-circle"></i>
                    </a>
                </li>
                <?php endif ?>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="themeToggle" title="Cambia tema">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </a>
                </li>
                <?= view_cell('App\Cells\AvvisiCell') ?>
                <li class="nav-item dropdown">
                    <?php
                        $u = auth()->user();
                        $p = (new \App\Models\PersonaleModel())->perUtente($u->id);
                        $displayName = $p ? trim($p['cognome'] . ' ' . $p['nome']) : $u->username;
                    ?>
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-md-inline"><?= esc($displayName) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= base_url('profilo') ?>"><i class="bi bi-person me-2"></i>Profilo</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalChangelog"><i class="bi bi-clock-history me-2"></i>Changelog</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Esci</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= base_url('/') ?>" class="brand-link">
                <span class="brand-text fw-semibold">Colombini Snc</span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <a href="<?= base_url('profilo') ?>" class="sidebar-user">
                <i class="bi bi-person-circle sidebar-user-avatar"></i>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?= esc($displayName) ?></span>
                    <span class="sidebar-user-role"><?= esc($u->getGroups()[0] ?? '') ?></span>
                </div>
            </a>
            <nav class="mt-2" aria-label="Navigazione principale">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" id="navigation">

                <li class="nav-header">Cruscotto</li>
                    <li class="nav-item">
                        <a href="<?= base_url('/') ?>" class="nav-link <?= uri_string() === '' ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('operativo/calendario') ?>" class="nav-link <?= str_starts_with(uri_string(), 'operativo/calendario') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-calendar3"></i>
                            <p>Calendario</p>
                        </a>
                    </li>
                    
                    <li class="nav-header">Anagrafiche</li>
                    <li class="nav-item">
                        <a href="<?= base_url('anagrafiche/clienti') ?>" class="nav-link <?= str_starts_with(uri_string(), 'anagrafiche/clienti') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-person-lines-fill"></i>
                            <p>Clienti</p>
                        </a>
                    </li>
                    <?php if (! $_soloTecnico): ?>
                        <li class="nav-item">
                            <a href="<?= base_url('anagrafiche/personale') ?>" class="nav-link <?= str_starts_with(uri_string(), 'anagrafiche/personale') ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Personale</p>
                            </a>
                        </li>
                        <?php endif ?>
                    <li class="nav-header">Operativo</li>
                    <?php
                        $interventiAttivo = str_starts_with(uri_string(), 'operativo/interventi');
                        $sezioneCorrente  = service('request')->getGet('sezione');
                        $iconeSezione     = [
                            'generale'    => 'bi-tools',
                            'piscine'     => 'bi-water',
                            'addolcitori' => 'bi-droplet',
                        ];
                    ?>
                    <li class="nav-item <?= $interventiAttivo ? 'menu-open' : '' ?>">
                        <a href="<?= base_url('operativo/interventi')?>" class="nav-link <?= $interventiAttivo ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-tools"></i>
                            <p>
                                Interventi
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach (\App\Models\TipiInterventoModel::CATEGORIE_LABEL as $cod => $lab): ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('operativo/interventi?sezione=' . $cod) ?>"
                                       class="nav-link <?= ($interventiAttivo && $sezioneCorrente === $cod) ? 'active' : '' ?>">
                                        <i class="nav-icon bi <?= $iconeSezione[$cod] ?? 'bi-tools' ?>"></i>
                                        <p><?= esc($lab) ?></p>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('abbonamenti') ?>" class="nav-link <?= str_starts_with(uri_string(), 'abbonamenti') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-file-earmark-text"></i>
                            <p>Abbonamenti</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('cantieri') ?>" class="nav-link <?= str_starts_with(uri_string(), 'cantieri') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-bricks"></i>
                            <p>Cantieri</p>
                        </a>
                    </li>
                    
                    <li class="nav-header">Magazzino</li>
                    <li class="nav-item">
                        <a href="<?= base_url('magazzino/articoli') ?>" class="nav-link <?= str_starts_with(uri_string(), 'magazzino') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-boxes"></i>
                            <p>Articoli</p>
                        </a>
                    </li>
                    
                    <?php if (! $_soloTecnico): ?>
                    <li class="nav-header">Amministrazione</li>
                    <li class="nav-item">
                        <a href="<?= base_url('impostazioni') ?>" class="nav-link <?= str_starts_with(uri_string(), 'impostazioni') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-gear"></i>
                            <p>Impostazioni</p>
                        </a>
                    </li>
                    <?php endif ?>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Main -->
    <main class="app-main">

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible m-3" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible m-3" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>

        <?php if (session()->getFlashdata('warning')): ?>
            <div class="alert alert-warning alert-dismissible m-3" role="alert">
                <?= session()->getFlashdata('warning') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>

        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h3 class="mb-0"><?= $this->renderSection('title') ?></h3>
                    </div>
                    <div class="col-sm-6">
                        <?= $this->renderSection('breadcrumb') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?= $this->renderSection('content') ?>
            </div>
        </div>

    </main>

    <footer class="app-footer">
        <?php if ($_cl['versioneCorrente'] !== ''): ?>
            <span class="text-muted small">v<?= esc($_cl['versioneCorrente']) ?></span>
        <?php endif ?>
        <span class="float-end text-muted small">Colombini SNC &copy; <?= date('Y') ?></span>
    </footer>

</div>

<?php if ($_mostraNovita): ?>
<div class="modal fade" id="modalNovita" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-stars me-2"></i>Novità — v<?= htmlspecialchars($_cl['versioneCorrente']) ?></h5>
            </div>
            <div class="modal-body" style="font-size:.9rem;"><?= $_cl['htmlNovita'] ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" id="btn-novita-ok">
                    <i class="bi bi-check-lg me-1"></i>Ho capito, grazie!
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<?= view_cell('App\Cells\PromemoriaOggiCell') ?>

<div class="modal fade" id="modalChangelog" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Changelog</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:.9rem;"><?= $_cl['htmlChangelog'] ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<?php if ($_helpFile): ?>
<div class="modal fade" id="modalHelp" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Guida</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body help-content"><?= $this->include('help/' . $_helpFile) ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<script src="<?= base_url('assets/vendor/popper/popper.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
<script src="<?= base_url('js/row-dblclick.js') ?>"></script>
<script>
    OverlayScrollbarsGlobal.OverlayScrollbars(document.querySelector('.sidebar-wrapper'), {
        scrollbars: { autoHide: 'leave' }
    });

    (() => {
        const key  = 'lte-theme';
        const html = document.documentElement;
        const btn  = document.getElementById('themeToggle');
        const icon = document.getElementById('themeIcon');

        function applyTheme(theme) {
            html.setAttribute('data-bs-theme', theme);
            html.style.colorScheme = theme;
            icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            try { localStorage.setItem(key, theme); } catch {}
        }

        applyTheme(html.getAttribute('data-bs-theme') || 'light');

        btn.addEventListener('click', () => {
            applyTheme(html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
        });
    })();

    <?php if ($_mostraNovita): ?>
    // In coda tramite enqueueModal (vedi script in cima al <body>): se ci sono anche
    // promemoria di oggi da mostrare, questo modal parte per primo e l'altro attende.
    enqueueModal('modalNovita', function () {
        var modal = new bootstrap.Modal(document.getElementById('modalNovita'));
        modal.show();
        document.getElementById('btn-novita-ok').addEventListener('click', function () {
            fetch('<?= base_url('profilo/versione-vista') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    versione: '<?= htmlspecialchars($_cl['versioneCorrente']) ?>',
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                })
            }).then(function (r) {
                if (r.ok) {
                    document.activeElement?.blur();
                    modal.hide();
                }
            });
        });
    });
    <?php endif ?>

    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        if (input.dataset.pwdToggleInit) return;
        input.dataset.pwdToggleInit = 'true';

        if (!input.parentElement.classList.contains('input-group')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'input-group';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        if (input.parentElement.querySelector('.input-group-text')) return;

        const toggle = document.createElement('span');
        toggle.className = 'input-group-text';
        toggle.style.cursor = 'pointer';
        toggle.title = 'Mostra/nascondi password';
        toggle.innerHTML = '<i class="bi bi-eye"></i>';
        input.parentElement.appendChild(toggle);

        toggle.addEventListener('click', function () {
            input.type = input.type === 'password' ? 'text' : 'password';
            toggle.querySelector('i').classList.toggle('bi-eye');
            toggle.querySelector('i').classList.toggle('bi-eye-slash');
        });
    });
</script>
<?= $this->renderSection('scripts') ?>
<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
});
</script>

</body>
</html>
