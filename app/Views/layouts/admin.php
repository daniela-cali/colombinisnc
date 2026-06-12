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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-md-inline">Utente</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Esci</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= base_url('/') ?>" class="brand-link">
                <span class="brand-text fw-semibold">Colombini SNC</span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Navigazione principale">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" id="navigation">
                    <li class="nav-item">
                        <a href="<?= base_url('/') ?>" class="nav-link">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
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
    OverlayScrollbars(document.querySelector('.sidebar-wrapper'), {
        scrollbars: { autoHide: 'leave' }
    });
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
