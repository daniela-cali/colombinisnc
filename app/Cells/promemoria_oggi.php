<?php
/**
 * @var array $oggi
 */
if ($oggi === []) return;
?>
<div class="modal fade" id="modalPromemoriaOggi" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-promemoria">
                <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>Promemoria di oggi</h5>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($oggi as $p): ?>
                    <li class="mb-3">
                        <span class="d-block small text-muted">
                            <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($p['data_ora_inizio'])) ?>
                        </span>
                        <span class="d-block fw-semibold"><?= esc($p['titolo']) ?></span>
                        <?php if (! empty($p['note'])): ?>
                        <span class="d-block small"><?= esc($p['note']) ?></span>
                        <?php endif ?>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-promemoria" id="btn-promemoria-oggi-ok">
                    <i class="bi bi-check-lg me-1"></i>Ho letto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ids  = <?= json_encode(array_column($oggi, 'id')) ?>;
    var hash = '<?= csrf_hash() ?>';
    var url  = '<?= base_url('promemoria/') ?>';

    // Dichiarata qui ma valorizzata dentro enqueueModal() perché dismissNext()
    // (definita più sotto) deve poterla usare una volta che il modal è stato mostrato.
    var modal;

    // Chiamate in sequenza (non in parallelo): il token CSRF si rigenera
    // ad ogni richiesta, quindi va aggiornato prima della successiva.
    function dismissNext(i) {
        if (i >= ids.length) {
            document.activeElement?.blur();
            modal.hide();
            return;
        }
        fetch(url + ids[i] + '/dismiss', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': hash },
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.csrf) hash = json.csrf;
                dismissNext(i + 1);
            });
    }

    document.getElementById('btn-promemoria-oggi-ok').addEventListener('click', function () {
        dismissNext(0);
    });

    // Non si apre subito: enqueueModal (definito in layouts/admin.php) lo mette in
    // coda con gli altri modal auto-aperti (es. novità di versione) e lo mostra
    // solo quando i precedenti sono stati chiusi, evitando che si sovrappongano.
    enqueueModal('modalPromemoriaOggi', function () {
        modal = new bootstrap.Modal(document.getElementById('modalPromemoriaOggi'));
        modal.show();
    });
});
</script>
