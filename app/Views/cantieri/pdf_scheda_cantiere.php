<?php
/**
 * @var array  $cantiere         Da CantieriModel::conCliente() — include cliente_denominazione
 * @var array  $cliente          Da ClientiModel::find() — anagrafica completa
 * @var array  $note             Da CantieriNoteModel::perCantiere() — diario completo
 * @var array  $interventi       Da InterventiModel::perCantiere(), ciascuno con chiave extra 'materiali' (InterventiMaterialiModel::perIntervento())
 * @var array  $tipiLabel        CantieriModel::TIPI_LABEL
 * @var array  $statiLabel       CantieriModel::STATI_LABEL
 * @var array  $interventiStatiLabel InterventiModel::STATI_LABEL
 * @var array  $materialiStatiLabel  InterventiMaterialiModel::STATI_LABEL
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

$cantiereBadge = [
    'aperto'  => 'st-cantiere-aperto',
    'sospeso' => 'st-cantiere-sospeso',
    'chiuso'  => 'st-cantiere-chiuso',
];

$interventoBadge = [
    'da_pianificare' => 'st-da-pianificare',
    'pianificato'    => 'st-pianificato',
    'in_corso'       => 'st-in-corso',
    'completato'     => 'st-completato',
    'annullato'      => 'st-annullato',
    'sospeso'        => 'st-sospeso',
];

$materialeBadge = [
    'da_portare' => 'st-da-pianificare',
    'consegnato' => 'st-completato',
];
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Scheda Cantiere — <?= esc($cantiere['titolo']) ?></title>
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
table.dettagli td.lbl { width: 34%; color: #6b7280; font-size: 10px; font-weight: bold; }
table.dettagli tr:last-child td { border-bottom: none; }

/* Badge */
.badge { display: inline; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: none; }
.st-da-pianificare { background: #e5e7eb; color: #374151; }
.st-pianificato     { background: #dbeafe; color: #1e40af; }
.st-in-corso        { background: #fef3c7; color: #92400e; }
.st-completato      { background: #d1fae5; color: #065f46; }
.st-annullato       { background: #fee2e2; color: #991b1b; }
.st-sospeso         { background: #ffedd5; color: #9a3412; }
.st-cantiere-aperto  { background: #d1fae5; color: #065f46; }
.st-cantiere-sospeso { background: #fef3c7; color: #92400e; }
.st-cantiere-chiuso  { background: #e5e7eb; color: #374151; }

/* Diario */
.nota-riga { font-size: 10px; padding: 5px 0; border-bottom: 1px solid #f3f4f6; page-break-inside: avoid; }
.nota-riga:last-child { border-bottom: none; }
.nota-data { display: inline-block; width: 62px; color: #2980b9; font-weight: bold; font-size: 9px; }
.nota-autore { color: #6b7280; font-size: 9px; }
.empty-small { color: #9ca3af; font-size: 9px; font-style: italic; }

/* Interventi + materiali */
.iv-blocco { margin-bottom: 10px; page-break-inside: avoid; }
table.iv-riga { width: 100%; border-collapse: collapse; background: #ebf5fb; }
table.iv-riga td { padding: 5px 7px; font-size: 10px; vertical-align: middle; }
.iv-codice { font-weight: bold; color: #2980b9; width: 90px; }
.iv-tipo   { color: #374151; }
.iv-tecnico{ color: #6b7280; font-size: 9px; }
.iv-data   { color: #6b7280; white-space: nowrap; width: 90px; }
.iv-stato  { text-align: right; width: 90px; }

table.materiali { width: 100%; border-collapse: collapse; margin: 3px 0 0; }
table.materiali td { padding: 3px 7px; border-bottom: 1px solid #f3f4f6; font-size: 9.5px; }
table.materiali tr:last-child td { border-bottom: none; }
.mat-qty  { width: 40px; text-align: center; color: #6b7280; }
.mat-stato{ width: 80px; text-align: right; }

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
        <div class="doc-title">Scheda Cantiere</div>
        <div class="doc-sub-strong"><?= esc($cantiere['titolo']) ?></div>
        <div class="doc-subtitle">Stampa: <?= date('d/m/Y H:i') ?></div>
    </td>
</tr></table>

<!-- Riquadri affiancati: cliente | cantiere -->
<table style="width:100%;"><tr>
    <td style="width:50%; vertical-align:top; padding-right:10px;">
        <h2>Cliente</h2>
        <table class="dettagli">
            <tr><td class="lbl">Denominazione</td><td><?= esc($cantiere['cliente_denominazione']) ?></td></tr>

            <?php if ($cliente['indirizzo'] || $cliente['citta']): ?>
            <tr>
                <td class="lbl">Indirizzo</td>
                <td>
                    <?= esc($cliente['indirizzo'] ?? '') ?>
                    <?php if ($cliente['cap'] || $cliente['citta']): ?>
                        <br><?= esc(trim(($cliente['cap'] ?? '') . ' ' . ($cliente['citta'] ?? ''))) ?><?= $cliente['provincia'] ? ' (' . esc($cliente['provincia']) . ')' : '' ?>
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
    </td>
    <td style="width:50%; vertical-align:top; padding-left:10px;">
        <h2>Cantiere</h2>
        <table class="dettagli">
            <tr><td class="lbl">Titolo</td><td><?= esc($cantiere['titolo']) ?></td></tr>
            <tr>
                <td class="lbl">Stato</td>
                <td>
                    <span class="badge <?= esc($cantiereBadge[$cantiere['stato']] ?? 'st-cantiere-chiuso') ?>">
                        <?= esc($statiLabel[$cantiere['stato']] ?? $cantiere['stato']) ?>
                    </span>
                </td>
            </tr>
            <tr><td class="lbl">Tipo</td><td><?= esc($tipiLabel[$cantiere['tipo']] ?? $cantiere['tipo']) ?></td></tr>
            <tr><td class="lbl">Inizio</td><td><?= $cantiere['data_inizio'] ? date('d/m/Y', strtotime($cantiere['data_inizio'])) : '—' ?></td></tr>
            <tr><td class="lbl">Fine prevista</td><td><?= $cantiere['data_fine_prevista'] ? date('d/m/Y', strtotime($cantiere['data_fine_prevista'])) : '—' ?></td></tr>
            <?php if ($cantiere['note']): ?>
            <tr><td class="lbl">Note</td><td><?= nl2br(esc($cantiere['note'])) ?></td></tr>
            <?php endif ?>
        </table>
    </td>
</tr></table>

<!-- Diario -->
<h2>Diario</h2>
<?php if (empty($note)): ?>
    <p class="empty-small">Nessuna nota nel diario.</p>
<?php else: ?>
    <?php foreach ($note as $n): ?>
    <div class="nota-riga">
        <span class="nota-data"><?= date('d/m/Y', strtotime($n['data_nota'])) ?></span>
        <?= nl2br(esc($n['testo'])) ?>
        <?php if (! empty($n['autore'])): ?>
            <span class="nota-autore"> — <?= esc($n['autore']) ?></span>
        <?php endif ?>
    </div>
    <?php endforeach ?>
<?php endif ?>

<!-- Elenco interventi con materiali -->
<h2>Interventi e materiali</h2>
<?php if (empty($interventi)): ?>
    <p class="empty-small">Nessun intervento collegato.</p>
<?php else: ?>
    <?php foreach ($interventi as $iv): ?>
    <div class="iv-blocco">
        <table class="iv-riga"><tr>
            <td class="iv-codice"><?= esc($iv['codice'] ?? '—') ?></td>
            <td class="iv-tipo">
                <?= esc($iv['tipo_intervento_nome'] ?? '—') ?>
                <?php if (! empty($iv['tecnico_nome'])): ?>
                    <span class="iv-tecnico"> — <?= esc($iv['tecnico_nome']) ?></span>
                <?php endif ?>
            </td>
            <td class="iv-data"><?= $iv['data_pianificata'] ? date('d/m/Y', strtotime($iv['data_pianificata'])) : '—' ?></td>
            <td class="iv-stato">
                <span class="badge <?= esc($interventoBadge[$iv['stato']] ?? 'st-da-pianificare') ?>">
                    <?= esc($interventiStatiLabel[$iv['stato']] ?? $iv['stato']) ?>
                </span>
            </td>
        </tr></table>

        <?php if (! empty($iv['materiali'])): ?>
        <table class="materiali">
            <?php foreach ($iv['materiali'] as $m): ?>
            <tr>
                <td><?= esc($m['desc_materiale'] ?? '') ?></td>
                <td class="mat-qty"><?= (int) $m['quantita'] ?></td>
                <td class="mat-stato">
                    <span class="badge <?= esc($materialeBadge[$m['stato']] ?? 'st-da-pianificare') ?>">
                        <?= esc($materialiStatiLabel[$m['stato']] ?? $m['stato']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
        </table>
        <?php endif ?>
    </div>
    <?php endforeach ?>
<?php endif ?>

<div class="footer">
    <?= esc(setting('Azienda.sede_nome') ?: 'Colombini Snc') ?> — Documento generato automaticamente il <?= date('d/m/Y \a\l\l\e H:i') ?>
</div>

</body>
</html>
