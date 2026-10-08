<?php
/**
 * Tag di <head> per icone e installazione come app (PWA).
 * Incluso da entrambi i layout con: <?= $this->include('partials/head_pwa') ?>
 *
 * Sta in un partial e non copiato nei due layout di proposito: due copie degli
 * stessi tag divergono, ed è esattamente così che era nato il difetto dello
 * sticky in v0.34.1 (l'altezza dell'header scritta a mano in due file).
 *
 * Le icone si rigenerano dal logo aziendale, vedi mobile_ux_spec.md §2.6.
 */
?>
<link rel="manifest" href="<?= base_url('manifest.json') ?>">
<meta name="theme-color" content="#1a6fa8">

<?php /* Nella scheda del browser la goccia del vecchio gestionale; la C resta per l'app
         in Home. L'ICO è il ripiego per chi non legge l'SVG: con sizes="any" Chrome lo
         preferirebbe all'SVG, per questo dichiara 32x32. */ ?>
<link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="32x32">
<link rel="icon" type="image/svg+xml" href="<?= asset_url('favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= asset_url('assets/icons/apple-touch-icon.png') ?>">

<?php /* iOS ignora il manifest per lo schermo intero: servono i suoi meta. */ ?>
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Colombini">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
