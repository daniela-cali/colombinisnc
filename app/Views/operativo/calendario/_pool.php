<?php
/**
 * Contenuto di #pool-container: interventi da pianificare raggruppati per zona/sottogruppo.
 * Condivisa da index.php (caricamento iniziale) e CalendarioController::poolPeriodo() (refresh
 * AJAX su navigazione prev/next del calendario) — un'unica "ricetta" per la card del pool.
 *
 * @var int   $totaleDaPianificare
 * @var array $poolPerZona            Map zona => blocchi[] (ciascuno: key, label, icona, interventi[])
 * @var array $totaliPerZona          Map zona => totale interventi
 * @var array $zoneLabel
 * @var array $tipiPerId
 * @var array $materialiPerIntervento
 */
$prioritaInfo = [
    'urgente'     => ['badge' => 'bg-danger',           'label' => 'Urgente'],
    'normale'     => ['badge' => 'bg-secondary',        'label' => 'Normale'],
    'programmato' => ['badge' => 'bg-primary',          'label' => 'Programmato'],
];
?>
<?php if ($totaleDaPianificare == 0): ?>
<div class="pool-empty">
    <i class="bi bi-check-circle text-success d-block mb-2" style="font-size:2rem;opacity:.5;"></i>
    Tutti pianificati
</div>
<?php else: ?>
<?php
$zonaStyleMap = [
    -1       => ['bg' => 'warning',   'text' => 'dark'],
     0       => ['bg' => 'success',   'text' => 'white'],
     1       => ['bg' => 'primary',   'text' => 'white'],
    'nessuna'=> ['bg' => 'secondary', 'text' => 'white'],
];
foreach ($poolPerZona as $zonaKey => $blocchi):
    $zonaNome   = $zoneLabel[$zonaKey] ?? 'Senza zona';
    $zonaStyle  = $zonaStyleMap[$zonaKey] ?? ['bg' => 'secondary', 'text' => 'white'];
    $collapseId = 'pool-zona-' . (is_int($zonaKey) ? ($zonaKey + 2) : 'nessuna');
?>
<div class="mb-1">
    <a class="d-flex align-items-center justify-content-between px-2 py-1 bg-<?= $zonaStyle['bg'] ?> text-<?= $zonaStyle['text'] ?>"
       data-bs-toggle="collapse" href="#<?= $collapseId ?>"
       style="border-radius:4px;text-decoration:none;">
        <span class="small fw-bold">
            <i class="bi bi-geo-alt me-1"></i>Zona <?= esc($zonaNome) ?>
        </span>
        <span class="badge bg-black bg-opacity-25"><?= $totaliPerZona[$zonaKey] ?></span>
    </a>
    <div class="collapse pool-zone show" id="<?= $collapseId ?>">
    <?php foreach ($blocchi as $blocco):
        $subCollapseId = $collapseId . '-' . $blocco['key'];
    ?>
    <div class="mb-1 ms-2">
        <a class="d-flex align-items-center justify-content-between px-2 py-1 bg-light text-dark small"
           data-bs-toggle="collapse" href="#<?= $subCollapseId ?>"
           style="border-radius:4px;text-decoration:none;">
            <span><i class="bi <?= esc($blocco['icona']) ?> me-1"></i><?= esc($blocco['label']) ?></span>
            <span class="badge bg-secondary"><?= count($blocco['interventi']) ?></span>
        </a>
        <div class="collapse" id="<?= $subCollapseId ?>">
        <?php foreach ($blocco['interventi'] as $i):
            $tipoInfo = $tipiPerId[(int) ($i['tipo_intervento_id'] ?? 0)]
                     ?? ['nome' => 'Senza tipo', 'icona' => 'fa-wrench', 'durata_default' => 60];
            if ($i['urgenza']) {
                $badge = 'bg-danger'; $badgeLabel = 'Urgente';
            } else {
                $pi = $prioritaInfo[$i['priorita']] ?? ['badge' => 'bg-secondary', 'label' => $i['priorita']];
                $badge = $pi['badge']; $badgeLabel = $pi['label'];
            }
            $materialiJson = json_encode(array_map(fn($m) => [
                'desc' => $m['desc_materiale'],
                'qta'  => $m['quantita'],
                'note' => $m['note'],
            ], $materialiPerIntervento[(int) $i['id']] ?? []));
        ?>
        <div class="pool-card <?= esc($i['urgenza'] ? 'urgente' : ($i['priorita'] ?? 'normale')) ?>"
             data-id="<?= $i['id'] ?>"
             data-tipo-id="<?= (int) ($i['tipo_intervento_id'] ?? 0) ?>"
             data-icona="<?= htmlspecialchars($tipoInfo['icona'] ?: 'fa-wrench', ENT_QUOTES) ?>"
             data-cliente="<?= htmlspecialchars($i['cliente_denominazione'] ?? '', ENT_QUOTES) ?>"
             data-durata="<?= (int) ($i['durata_stimata'] ?: $tipoInfo['durata_default']) ?>"
             data-tipo-nome="<?= htmlspecialchars($tipoInfo['nome'], ENT_QUOTES) ?>"
             data-descr="<?= htmlspecialchars($i['descrizione'] ?? '', ENT_QUOTES) ?>"
             data-scadenza="<?= esc($i['data_scadenza'] ?? '') ?>"
             data-creato="<?= esc($i['created_at'] ?? '') ?>"
             data-urgenza="<?= (int) ($i['urgenza'] ?? 0) ?>"
             data-materiali="<?= esc($materialiJson, 'attr') ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="d-flex gap-1">
                    <span class="badge <?= esc($badge) ?>" style="font-size:.65rem;"><?= esc($badgeLabel) ?></span>
                    <?php if (! empty($i['extra'])): ?>
                    <span class="badge bg-warning text-dark" style="font-size:.65rem;">Extra</span>
                    <?php endif ?>
                </div>
                <small class="text-muted">
                    <?php if (!empty($i['data_scadenza'])): ?>
                        <i class="bi bi-clock" style="font-size:.65rem;"></i> <?= date('d/m', strtotime($i['data_scadenza'])) ?> ·
                    <?php endif; ?>
                    #<?= $i['id'] ?>
                    <?php if (!empty($i['distanza_sede'])): ?>
                        · <?= number_format((float)$i['distanza_sede'], 1) ?> km
                    <?php endif; ?>
                </small>
            </div>
            <div class="fw-bold small mb-1 text-truncate">
                <?= !empty($i['cliente_denominazione']) ? esc($i['cliente_denominazione']) : '<em class="text-muted">Senza cliente</em>' ?>
            </div>
            <?php if (!empty($i['cliente_citta'])): ?>
            <div class="small text-muted mb-1 text-truncate">
                <i class="bi bi-geo-alt me-1"></i><?= esc($i['cliente_citta']) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($i['descrizione'])): ?>
            <div class="small text-muted text-truncate" style="font-size:.74rem;">
                <?= esc(mb_strimwidth($i['descrizione'], 0, 55, '…')) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; // interventi del blocco ?>
        </div>
    </div>
    <?php endforeach; // blocchi della zona ?>
    </div>
</div>
<?php endforeach; // zone ?>
<?php endif; ?>
