<?php
/**
 * Asset JS condivisi per le view che usano DataTables (con estensione Responsive).
 * Incluso dentro section('scripts') con: <?= $this->include('partials/datatables_scripts') ?>
 * L'ordine è vincolante: DataTables core → integrazione BS5 → Responsive core → Responsive BS5.
 * Da DataTables 3 jQuery non serve più: il progetto non lo carica.
 * In coda datatable-init.js, che definisce initTabella(): sta qui e non nelle
 * singole view perché serve a tutte, e così nessuna può dimenticarselo.
 */
?>
<script src="<?= asset_url('assets/vendor/datatables/dataTables.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/datatables/dataTables.responsive.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/datatables/responsive.bootstrap5.min.js') ?>"></script>
<script src="<?= asset_url('js/datatable-init.js') ?>"></script>
