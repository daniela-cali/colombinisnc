<?php
/**
 * @var array  $cliente             Record clienti con tutti i campi
 * @var string $cliente_denominazione Denominazione calcolata (ClientiModel::denominazione())
 * @var array  $sospesi             Righe da InterventiMaterialiModel::sospesiPerCliente()
 * @var array  $daPianificare       Interventi con stato = da_pianificare
 * @var array  $pianificati         Interventi con stato in (pianificato, in_corso)
 * @var array  $prioritaLabel       InterventiModel::PRIORITA_LABEL
 * @var array  $abbonamenti         Abbonamenti con stato_calcolato = attivo
 * @var array  $abbonamentiFrequenze AbbonamentiModel::FREQUENZE_LABEL
 * @var array  $cantieri            Cantieri aperti/sospesi, ciascuno con chiavi extra 'note' e 'interventi' (ultimi 3)
 * @var array  $cantieriStatiLabel  CantieriModel::STATI_LABEL
 */

$logoPath   = setting('Azienda.sede_logo_path');
$logoBase64 = '';
if ($logoPath && is_file(FCPATH . $logoPath)) {
    $mime       = mime_content_type(FCPATH . $logoPath) ?: 'image/png';
    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(FCPATH . $logoPath));
}

$aziendaRiga = array_filter([
    setting('Azienda.sede_indirizzo'),
    trim((setting('Azienda.sede_cap') ?? '') . ' ' . (setting('Azienda.sede_citta') ?? '')),
    setting('Azienda.sede_telefono'),
]);

$zonaLabels = ['-1' => 'Ventimiglia', '0' => 'Ceriale', '1' => 'Savona'];
$zonaLabel  = ($cliente['zona'] !== null) ? ($zonaLabels[(string) (int) $cliente['zona']] ?? null) : null;

$prioritaBadge = [
    'urgente'     => 'urgente',
    'normale'     => 'ordinario',
    'abbonamento' => 'programmato',
];

$cantieriStatoBadge = [
    'aperto'  => 's-completato',
    'sospeso' => 's-in_corso',
];

$gruppiInterventi = [
    ['titolo' => 'Interventi da pianificare', 'lista' => $daPianificare],
    ['titolo' => 'Interventi pianificati',    'lista' => $pianificati],
];
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Scheda Cliente — <?= esc($cliente_denominazione) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; padding: 24px 28px; }

/* Header */
table.header { width: 100%; border-bottom: 2px solid #2980b9; margin-bottom: 18px; padding-bottom: 12px; }
.company-name { font-size: 15px; font-weight: bold; color: #1f2937; }
.company-info { color: #6b7280; font-size: 10px; margin-top: 3px; }
.doc-title     { font-size: 17px; font-weight: bold; color: #2980b9; text-align: right; }
.doc-sub-strong{ font-size: 12px; font-weight: bold; color: #1f2937; text-align: right; margin-top: 3px; }
.doc-subtitle   { font-size: 10px; color: #6b7280; text-align: right; margin-top: 2px; }

/* Sezioni */
h2 { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .05em;
     color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin: 18px 0 10px; }

/* Tabella etichetta/valore */
table.dettagli { width: 100%; border-collapse: collapse; }
table.dettagli td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
table.dettagli td.lbl { width: 32%; color: #6b7280; font-size: 10px; font-weight: bold; }
table.dettagli tr:last-child td { border-bottom: none; }

/* Badge */
.badge { display: inline; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: none; }
.urgente      { background: #fee2e2; color: #991b1b; }
.ordinario    { background: #e5e7eb; color: #374151; }
.programmato  { background: #dbeafe; color: #1e40af; }
.s-completato { background: #d1fae5; color: #065f46; }
.s-in_corso   { background: #fef3c7; color: #92400e; }

/* Tabella materiali */
table.materiali { width: 100%; border-collapse: collapse; }
table.materiali th { font-size: 10px; font-weight: bold; color: #6b7280; text-transform: uppercase;
                     border-bottom: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
table.materiali td { font-size: 11px; padding: 5px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
table.materiali td.qty { width: 50px; text-align: center; font-weight: bold; }
table.materiali tr:last-child td { border-bottom: none; }

/* Tabella interventi */
table.tappe { width: 100%; border-collapse: collapse; }
table.tappe thead tr { background: #ebf5fb; }
table.tappe thead th { padding: 5px 7px; text-align: left; font-size: 9px; color: #374151; font-weight: bold; border-bottom: 1px solid #d1d5db; }
table.tappe tbody td { padding: 5px 7px; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 10px; }
table.tappe tbody tr:last-child td { border-bottom: none; }
table.tappe tbody tr:nth-child(even) { background: #f9fafb; }
.data-col { width: 62px; color: #2980b9; font-weight: bold; white-space: nowrap; }
.scad-col { width: 55px; color: #6b7280; white-space: nowrap; }
.tipo-col { width: 90px; color: #6b7280; font-size: 9px; }
.pri-col  { width: 65px; }

/* Cantieri: due colonne affiancate */
.sotto-label { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em;
               color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 4px; }
.nota-riga { font-size: 10px; padding: 4px 0; border-bottom: 1px solid #f3f4f6; }
.nota-data { display: inline-block; width: 58px; color: #2980b9; font-weight: bold; font-size: 9px; }
.empty-small { color: #9ca3af; font-size: 9px; font-style: italic; }

/* Footer */
.footer { border-top: 1px solid #e5e7eb; margin-top: 24px; padding-top: 7px;
          text-align: center; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>

<!-- Intestazione -->
<table class="header"><tr>
    <td style="width:55%;">
        <?php if ($logoBase64): ?>
            <img src="<?= $logoBase64 ?>" alt="Logo" style="max-height:42px;max-width:170px;margin-bottom:4px;display:block;">
        <?php else: ?>
            <div class="company-name"><?= esc(setting('Azienda.sede_nome') ?: 'Colombini Snc') ?></div>
        <?php endif ?>
        <div class="company-info"><?= esc(implode(' — ', $aziendaRiga)) ?></div>
    </td>
    <td style="width:45%; vertical-align:top;">
        <div class="doc-title">Scheda Cliente</div>
        <div class="doc-sub-strong"><?= esc($cliente_denominazione) ?></div>
        <div class="doc-subtitle">Stampa: <?= date('d/m/Y H:i') ?></div>
    </td>
</tr></table>

<!-- Anagrafica -->
<h2>Anagrafica</h2>
<table class="dettagli">
    <tr><td class="lbl">Denominazione</td><td><?= esc($cliente_denominazione) ?></td></tr>

    <?php if ($cliente['indirizzo'] || $cliente['citta']): ?>
    <tr>
        <td class="lbl">Indirizzo</td>
        <td>
            <?= esc($cliente['indirizzo'] ?? '') ?>
            <?php if ($cliente['cap'] || $cliente['citta']): ?>
                <br><?= esc(trim(($cliente['cap'] ?? '') . ' ' . ($cliente['citta'] ?? ''))) ?><?= $cliente['provincia'] ? ' (' . esc($cliente['provincia']) . ')' : '' ?>
            <?php endif ?>
            <?php if ($cliente['lat'] !== null && $cliente['lng'] !== null): ?>
                <br><a href="https://www.google.com/maps/dir/?api=1&destination=<?= esc($cliente['lat']) ?>,<?= esc($cliente['lng']) ?>">Apri in Google Maps</a>
            <?php endif ?>
        </td>
    </tr>
    <?php endif ?>

    <?php if ($zonaLabel): ?>
    <tr><td class="lbl">Zona</td><td><?= esc($zonaLabel) ?></td></tr>
    <?php endif ?>

    <?php if ($cliente['telefono']): ?>
    <tr><td class="lbl">Telefono</td><td><?= esc($cliente['telefono']) ?></td></tr>
    <?php endif ?>

    <?php if ($cliente['email']): ?>
    <tr><td class="lbl">Email</td><td><?= esc($cliente['email']) ?></td></tr>
    <?php endif ?>

    <?php if ($cliente['piva']): ?>
    <tr><td class="lbl">P.IVA</td><td><?= esc($cliente['piva']) ?></td></tr>
    <?php endif ?>

    <?php if ($cliente['cfisc']): ?>
    <tr><td class="lbl">Cod. fiscale</td><td><?= esc($cliente['cfisc']) ?></td></tr>
    <?php endif ?>

    <?php if ($cliente['note']): ?>
    <tr><td class="lbl">Note</td><td><?= nl2br(esc($cliente['note'])) ?></td></tr>
    <?php endif ?>
</table>

<!-- Materiali da portare -->
<?php if (! empty($sospesi)): ?>
<h2>Materiali da portare</h2>
<table class="materiali">
    <thead>
        <tr><th>Descrizione</th><th style="width:50px;text-align:center;">Qtà</th><th>Note</th></tr>
    </thead>
    <tbody>
        <?php foreach ($sospesi as $s): ?>
        <tr>
            <td><?= esc($s['desc_materiale']) ?></td>
            <td class="qty"><?= (int) $s['quantita'] ?></td>
            <td style="color:#6b7280;"><?= esc($s['note'] ?? '') ?></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<!-- Interventi: da pianificare / pianificati -->
<?php foreach ($gruppiInterventi as $g): if (empty($g['lista'])) continue; ?>
<h2><?= esc($g['titolo']) ?></h2>
<table class="tappe">
    <thead>
        <tr>
            <th class="data-col">Pianificato</th>
            <th class="scad-col">Scadenza</th>
            <th class="tipo-col">Tipo</th>
            <th>Descrizione</th>
            <th class="pri-col">Priorità</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($g['lista'] as $iv): ?>
        <tr>
            <td class="data-col"><?= $iv['data_pianificata'] ? date('d/m/Y H:i', strtotime($iv['data_pianificata'])) : '—' ?></td>
            <td class="scad-col"><?= $iv['data_scadenza'] ? date('d/m/Y', strtotime($iv['data_scadenza'])) : '—' ?></td>
            <td class="tipo-col"><?= esc($iv['tipo_intervento_nome'] ?? '—') ?></td>
            <td>
                <?= esc($iv['descrizione'] ?? '') ?>
                <?php if (! empty($iv['urgenza'])): ?>
                    <br><span style="color:#991b1b;font-size:9px;font-weight:bold;">&#9888; Urgente</span>
                <?php endif ?>
            </td>
            <td class="pri-col">
                <span class="badge <?= esc($prioritaBadge[$iv['priorita']] ?? 'ordinario') ?>">
                    <?= esc($prioritaLabel[$iv['priorita']] ?? $iv['priorita']) ?>
                </span>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endforeach ?>

<!-- Abbonamento attivo -->
<?php if (! empty($abbonamenti)): ?>
<h2><?= count($abbonamenti) > 1 ? 'Abbonamenti attivi' : 'Abbonamento attivo' ?></h2>
<table class="dettagli">
    <?php foreach ($abbonamenti as $ab): ?>
    <tr><td class="lbl">Tipo</td><td><?= esc($ab['tipo_nome'] ?? '—') ?></td></tr>
    <?php if (count($ab['periodi']) <= 1): ?>
    <tr>
        <td class="lbl">Frequenza</td>
        <td><?= esc($abbonamentiFrequenze[$ab['prima_frequenza']] ?? '—') ?></td>
    </tr>
    <tr>
        <td class="lbl">Periodo</td>
        <td><?= date('d/m/Y', strtotime($ab['data_inizio'])) ?> – <?= date('d/m/Y', strtotime($ab['data_fine'])) ?></td>
    </tr>
    <?php else: ?>
    <tr>
        <td class="lbl">Periodi</td>
        <td>
            <?php foreach ($ab['periodi'] as $p): ?>
            <div>
                <?= date('d/m/Y', strtotime($p['data_inizio'])) ?> – <?= date('d/m/Y', strtotime($p['data_fine'])) ?>
                &nbsp;—&nbsp;<?= esc($abbonamentiFrequenze[$p['frequenza']] ?? $p['frequenza']) ?>
                <?php if (! empty($p['con_pulizia_fondo'])): ?>
                    <span style="color:#6b7280;font-size:9px;">(+ pulizia fondo)</span>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </td>
    </tr>
    <?php endif ?>
    <?php endforeach ?>
</table>
<?php endif ?>

<!-- Cantieri -->
<?php foreach ($cantieri as $ct): ?>
<h2>
    Cantiere — <?= esc($ct['titolo']) ?>
    <span class="badge <?= esc($cantieriStatoBadge[$ct['stato']] ?? 'ordinario') ?>">
        <?= esc($cantieriStatiLabel[$ct['stato']] ?? $ct['stato']) ?>
    </span>
</h2>
<table style="width:100%;"><tr>
    <td style="width:50%;vertical-align:top;padding-right:10px;">
        <div class="sotto-label">Ultime note</div>
        <?php if (empty($ct['note'])): ?>
            <p class="empty-small">Nessuna nota.</p>
        <?php else: ?>
            <?php foreach ($ct['note'] as $n): ?>
            <div class="nota-riga">
                <span class="nota-data"><?= date('d/m/Y', strtotime($n['data_nota'])) ?></span>
                <?= esc(mb_strimwidth($n['testo'], 0, 130, '…')) ?>
            </div>
            <?php endforeach ?>
        <?php endif ?>
    </td>
    <td style="width:50%;vertical-align:top;padding-left:10px;">
        <div class="sotto-label">Ultimi interventi</div>
        <?php if (empty($ct['interventi'])): ?>
            <p class="empty-small">Nessun intervento.</p>
        <?php else: ?>
            <?php foreach ($ct['interventi'] as $iv): ?>
            <div class="nota-riga">
                <span class="nota-data"><?= $iv['data_pianificata'] ? date('d/m/Y', strtotime($iv['data_pianificata'])) : '—' ?></span>
                <?= esc($iv['tipo_intervento_nome'] ?? $iv['descrizione'] ?? '—') ?>
            </div>
            <?php endforeach ?>
        <?php endif ?>
    </td>
</tr></table>
<?php endforeach ?>

<div class="footer">
    <?= esc(setting('Azienda.sede_nome') ?: 'Colombini Snc') ?> — Documento generato automaticamente il <?= date('d/m/Y \a\l\l\e H:i') ?>
</div>

</body>
</html>
