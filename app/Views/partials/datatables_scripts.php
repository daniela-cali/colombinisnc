<?php
/**
 * Asset JS condivisi per le view che usano DataTables (con estensione Responsive).
 * Incluso dentro section('scripts') con: <?= $this->include('partials/datatables_scripts') ?>
 * L'ordine è vincolante: jQuery → DataTables core → integrazione BS5 → Responsive core → Responsive BS5.
 * jQuery è incluso qui perché nel progetto serve esclusivamente a DataTables.
 */
?>
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.responsive.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/responsive.bootstrap5.min.js') ?>"></script>
