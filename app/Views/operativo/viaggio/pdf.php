<?php
/**
 * @var string  $data
 * @var string  $dataLabel
 * @var array[] $perZona
 * @var array   $zoneLabel
 * @var int     $totale
 * @var array[] $materialiPerIntervento
 */

$zonaColors = [
    -1       => ['bg' => '#ffc107', 'fg' => '#000000'],
     0       => ['bg' => '#198754', 'fg' => '#ffffff'],
     1       => ['bg' => '#0d6efd', 'fg' => '#ffffff'],
    'nessuna'=> ['bg' => '#6c757d', 'fg' => '#ffffff'],
];
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Foglio Viaggio — <?= esc($dataLabel) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'DejaVu Sans', Helvetica, sans-serif;
        font-size: 10px;
        color: #111;
        background: #fff;
        padding: 12mm 14mm;
    }

    /* ---- Intestazione ---- */
    .header {
        border-bottom: 2px solid #333;
        padding-bottom: 6px;
        margin-bottom: 14px;
    }
    .header-title {
        font-size: 16px;
        font-weight: bold;
        letter-spacing: .5px;
    }
    .header-sub {
        font-size: 10px;
        color: #555;
        margin-top: 2px;
    }

    /* ---- Sezione zona ---- */
    .zona-block {
        margin-bottom: 18px;
    }
    .zona-header {
        padding: 5px 8px;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: .3px;
    }

    /* ---- Tabella interventi ---- */
    table {
        width: 100%;
        border-collapse: collapse;
    }
    thead th {
        background: #e8e8e8;
        border: 1px solid #bbb;
        padding: 4px 6px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    tbody td {
        border: 1px solid #ccc;
        padding: 5px 6px;
        vertical-align: top;
    }
    tbody tr:nth-child(even) td {
        background: #f9f9f9;
    }
    tbody tr.urgente td {
        background: #fff0f0;
    }

    .ora {
        font-weight: bold;
        font-size: 11px;
        white-space: nowrap;
        width: 48px;
    }
    .urgente-badge {
        font-size: 8px;
        color: #cc0000;
        font-weight: bold;
        display: block;
    }
    .cliente-nome {
        font-weight: bold;
        font-size: 10px;
    }
    .cliente-citta {
        font-size: 9px;
        color: #555;
    }
    .indirizzo {
        font-size: 9px;
        color: #444;
        width: 130px;
    }
    .tipo-nome {
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: .2px;
        color: #333;
    }
    .descrizione {
        font-size: 10px;
        color: #222;
        margin-top: 2px;
    }
    .materiali {
        margin-top: 5px;
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
    .materiali-item {
        display: block;
        padding-left: 6px;
    }
    .materiali-note {
        color: #888;
        font-style: italic;
    }
    .tecnico {
        font-size: 9px;
        white-space: nowrap;
        width: 100px;
    }
    .firma-col {
        width: 60px;
    }

    /* ---- Footer pagina ---- */
    .footer {
        margin-top: 18px;
        border-top: 1px solid #ccc;
        padding-top: 4px;
        font-size: 8px;
        color: #888;
        text-align: right;
    }
</style>
</head>
<body>

<div class="header">
    <div class="header-title">Foglio di Viaggio &mdash; <?= esc($dataLabel) ?></div>
    <div class="header-sub">Colombini SNC &mdash; <?= $totale ?> interventi pianificati</div>
</div>

<?php if (empty($perZona)): ?>
<p style="text-align:center; color:#888; margin-top:40px;">Nessun intervento pianificato per questo giorno.</p>
<?php else: ?>

<?php foreach ($perZona as $zonaKey => $interventi):
    $zonaNome  = $zoneLabel[$zonaKey] ?? 'Senza zona';
    $colors    = $zonaColors[$zonaKey] ?? ['bg' => '#6c757d', 'fg' => '#ffffff'];
?>
<div class="zona-block">

    <div class="zona-header" style="background:<?= $colors['bg'] ?>;color:<?= $colors['fg'] ?>;">
        Zona <?= esc($zonaNome) ?> &nbsp;&mdash;&nbsp; <?= count($interventi) ?> interventi
    </div>

    <table>
        <thead>
            <tr>
                <th class="ora">Ora</th>
                <th style="width:130px">Cliente</th>
                <th class="indirizzo">Indirizzo</th>
                <th>Tipo / Descrizione / Materiali</th>
                <th class="tecnico">Tecnico</th>
                <th class="firma-col">Firma</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($interventi as $i): ?>
        <tr class="<?= $i['urgenza'] ? 'urgente' : '' ?>">
            <td class="ora">
                <?= date('H:i', strtotime($i['data_pianificata'])) ?>
                <?php if ($i['urgenza']): ?>
                    <span class="urgente-badge">&#9888; URGENTE</span>
                <?php endif ?>
            </td>
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
            <td class="tecnico"><?= esc($i['tecnico_nome'] ?: '—') ?></td>
            <td class="firma-col">&nbsp;</td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>

</div>
<?php endforeach ?>
<?php endif ?>

<div class="footer">
    Stampato il <?= date('d/m/Y H:i') ?> &mdash; Colombini SNC
</div>

</body>
</html>
