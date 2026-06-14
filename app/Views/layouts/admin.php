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
    <link rel="stylesheet" href="<?= base_url('css/custom.css') ?>">
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
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
                <li class="nav-item">
                    <a class="nav-link" href="#" id="themeToggle" title="Cambia tema">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </a>
                </li>
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
                <?php $logo = setting('Azienda.sede_logo_path'); ?>
                <?php if ($logo): ?>
                    <img src="<?= base_url($logo) ?>" alt="Logo" class="sidebar-logo">
                <?php else: ?>
                    <span class="brand-text fw-semibold">Colombini SNC</span>
                <?php endif ?>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Navigazione principale">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" id="navigation">

                <li class="nav-header">Cruscotto</li>
                    <li class="nav-item">
                        <a href="<?= base_url('/') ?>" class="nav-link <?= uri_string() === '' ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">Anagrafiche</li>
                    <li class="nav-item">
                        <a href="<?= base_url('anagrafiche/personale') ?>" class="nav-link <?= str_starts_with(uri_string(), 'anagrafiche/personale') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-people"></i>
                            <p>Personale</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('anagrafiche/clienti') ?>" class="nav-link <?= str_starts_with(uri_string(), 'anagrafiche/clienti') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-person-lines-fill"></i>
                            <p>Clienti</p>
                        </a>
                    </li>

                    <li class="nav-header">Amministrazione</li>
                    <li class="nav-item">
                        <a href="<?= base_url('impostazioni') ?>" class="nav-link <?= str_starts_with(uri_string(), 'impostazioni') ? 'active' : '' ?>">
                            <i class="nav-icon bi bi-gear"></i>
                            <p>Impostazioni</p>
                        </a>
                    </li>

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
        <span class="float-end text-muted small">Colombini SNC &copy; <?= date('Y') ?></span>
    </footer>

</div>

<script src="<?= base_url('assets/vendor/popper/popper.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
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

</body>
</html>
