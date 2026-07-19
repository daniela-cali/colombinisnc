<?php
/**
 * @var string      $data
 * @var string      $dataLabel
 * @var array[]     $perZona
 * @var array       $zoneLabel
 * @var int         $totale
 * @var array[]     $materialiPerIntervento
 * @var string|null $tecnicoNome Se valorizzato, il PDF è filtrato sul singolo tecnico
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

$zonaColors = [
    -1       => ['bg' => '#ffc107', 'fg' => '#000000'],
     0       => ['bg' => '#198754', 'fg' => '#ffffff'],
     1       => ['bg' => '#0d6efd', 'fg' => '#ffffff'],
    'nessuna'=> ['bg' => '#6c757d', 'fg' => '#ffffff'],
];

$prioritaBadgeClass = ['urgente' => 'urgente', 'normale' => 'ordinario', 'abbonamento' => 'programmato'];
$prioritaBadgeLabel = \App\Models\InterventiModel::PRIORITA_LABEL;
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Foglio di Viaggio — <?= esc($dataLabel) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; padding: 20px 26px; }

/* Header */
table.header { width: 100%; border-bottom: 2px solid #2980b9; margin-bottom: 16px; padding-bottom: 10px; }
.company-name   { font-size: 14px; font-weight: bold; color: #1f2937; }
.company-info   { color: #6b7280; font-size: 9px; margin-top: 2px; }
.doc-title      { font-size: 16px; font-weight: bold; color: #2980b9; text-align: right; }
.doc-sub-strong { font-size: 12px; font-weight: bold; color: #1f2937; text-align: right; margin-top: 2px; }
.doc-subtitle   { font-size: 9px; color: #6b7280; text-align: right; margin-top: 2px; }

/* Blocco zona */
.zona-block { margin-bottom: 18px; page-break-inside: avoid; }
.zona-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: bold;
    border-radius: 3px 3px 0 0;
}
.zona-header .conteggio { font-size: 9px; font-weight: normal; opacity: .85; }

/* Tabella tappe */
table.tappe {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #d1d5db;
    border-top: none;
    border-radius: 0 0 3px 3px;
}
table.tappe thead tr { background: #ebf5fb; }
table.tappe thead th {
    padding: 5px 7px;
    text-align: left;
    font-size: 9px;
    color: #374151;
    font-weight: bold;
    border-bottom: 1px solid #d1d5db;
}
table.tappe tbody td {
    padding: 5px 7px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
    font-size: 10px;
}
table.tappe tbody tr:last-child td { border-bottom: none; }
table.tappe tbody tr:nth-child(even) { background: #f9fafb; }
table.tappe tbody tr.urgente td { background: #fff0f0; }

.ora        { width: 42px; color: #2980b9; font-weight: bold; white-space: nowrap; }
.indirizzo  { font-size: 9px; color: #6b7280; width: 120px; }
.tipo-nome  { display: block; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: .2px; color: #374151; }
.descrizione{ display: block; font-size: 10px; color: #374151; margin-top: 2px; }
.cliente-nome { font-weight: bold; font-size: 10px; }
.cliente-citta{ display: block; font-size: 9px; color: #6b7280; }
.tecnico-col{ width: 90px; font-size: 9px; }
.pri-col    { width: 62px; }
.firma-col  { width: 55px; }

/* Badge priorità */
.badge { display: inline; padding: 2px 6px; border-radius: 2px; font-size: 9px; font-weight: bold; }
.urgente     { background: #fee2e2; color: #991b1b; }
.ordinario   { background: #e5e7eb; color: #374151; }
.programmato { background: #dbeafe; color: #1e40af; }

/* Materiali da portare */
.materiali {
    margin-top: 4px;
    padding: 4px 6px;
    background: #fff8e1;
    border-left: 3px solid #ffc107;
    font-size: 9px;
    color: #444;
}
.materiali-label {
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .3px;
    font-size: 8px;
    color: #888;
    display: block;
    margin-bottom: 2px;
}
.materiali-item { display: block; padding-left: 6px; }
.materiali-note { color: #888; font-style: italic; }

/* Nessun intervento */
.empty { text-align: center; color: #9ca3af; padding: 30px; font-size: 11px; }

/* Footer */
.footer {
    border-top: 1px solid #e5e7eb;
    margin-top: 20px;
    padding-top: 6px;
    text-align: center;
    font-size: 8px;
    color: #9ca3af;
}
</style>
</head>
<body>

<!-- Intestazione -->
<table class="header"><tr>
    <td style="width:55%;">
        <?php if ($logoBase64): ?>
            <img src="<?= $logoBase64 ?>" alt="Logo" style="max-height:40px;max-width:160px;margin-bottom:3px;display:block;">
        <?php else: ?>
            <div class="company-name"><?= esc(setting('Azienda.sede_nome') ?: 'Colombini Snc') ?></div>
        <?php endif ?>
        <div class="company-info"><?= esc(implode(' — ', $aziendaRiga)) ?></div>
    </td>
    <td style="width:45%; vertical-align:top;">
        <div class="doc-title">Foglio di Viaggio</div>
        <div class="doc-sub-strong"><?= esc($dataLabel) ?><?= $tecnicoNome ? ' — ' . esc($tecnicoNome) : '' ?></div>
        <div class="doc-subtitle"><?= $totale ?> interventi pianificati &nbsp;|&nbsp; Stampa: <?= date('d/m/Y H:i') ?></div>
    </td>
</tr></table>

<?php if (empty($perZona)): ?>
<div class="empty">Nessun intervento pianificato per questo giorno.</div>
<?php else: ?>

<?php foreach ($perZona as $zonaKey => $interventi):
    $zonaNome = $zoneLabel[$zonaKey] ?? 'Senza zona';
    $colori   = $zonaColors[$zonaKey] ?? ['bg' => '#6c757d', 'fg' => '#ffffff'];
?>
<div class="zona-block">

    <div class="zona-header" style="background:<?= $colori['bg'] ?>;color:<?= $colori['fg'] ?>;">
        Zona <?= esc($zonaNome) ?> <span class="conteggio">&nbsp;&mdash;&nbsp; <?= count($interventi) ?> interventi</span>
    </div>

    <table class="tappe">
        <thead>
            <tr>
                <th class="ora">Ora</th>
                <th>Cliente</th>
                <th class="indirizzo">Indirizzo</th>
                <th>Tipo / Descrizione</th>
                <th class="pri-col">Priorità</th>
                <th class="tecnico-col">Tecnico</th>
                <th class="firma-col">Firma</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($interventi as $i):
            if ($i['urgenza']) {
                $badgeClass = 'urgente';
                $badgeLabel = 'Urgente';
            } else {
                $badgeClass = $prioritaBadgeClass[$i['priorita']] ?? 'ordinario';
                $badgeLabel = $prioritaBadgeLabel[$i['priorita']] ?? $i['priorita'];
            }
        ?>
        <tr class="<?= $i['urgenza'] ? 'urgente' : '' ?>">
            <td class="ora"><?= date('H:i', strtotime($i['data_pianificata'])) ?></td>
            <td>
                <span class="cliente-nome"><?= esc($i['cliente_denominazione'] ?? '—') ?></span>
                <?php if ($i['citta']): ?>
                    <span class="cliente-citta"><?= esc($i['citta']) ?></span>
                <?php endif ?>
            </td>
            <td class="indirizzo"><?= esc($i['indirizzo'] ?? '—') ?></td>
            <td>
                <?php if ($i['tipo_nome']): ?>
                    <span class="tipo-nome"><?= esc($i['tipo_nome']) ?></span>
                <?php endif ?>
                <?php if ($i['descrizione']): ?>
                    <span class="descrizione"><?= esc($i['descrizione']) ?></span>
                <?php endif ?>
                <?php if (!empty($materialiPerIntervento[$i['id']])): ?>
                <div class="materiali">
                    <span class="materiali-label">Materiali da portare</span>
                    <?php foreach ($materialiPerIntervento[$i['id']] as $m): ?>
                    <span class="materiali-item">
                        &bull; <?= (int) $m['quantita'] ?>&times; <?= esc($m['descrizione']) ?>
                        <?php if ($m['note']): ?>
                            <span class="materiali-note">(<?= esc($m['note']) ?>)</span>
                        <?php endif ?>
                    </span>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </td>
            <td class="pri-col"><span class="badge <?= $badgeClass ?>"><?= esc($badgeLabel) ?></span></td>
            <td class="tecnico-col"><?= esc($i['tecnico_nome'] ?: '—') ?></td>
            <td class="firma-col">&nbsp;</td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>

</div>
<?php endforeach ?>
<?php endif ?>

<div class="footer">
    <?= esc(setting('Azienda.sede_nome') ?: 'Colombini Snc') ?> — Documento generato automaticamente il <?= date('d/m/Y \a\l\l\e H:i') ?>
</div>

</body>
</html>
