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

<link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/icons/icon-32.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/icons/apple-touch-icon.png') ?>">

<?php /* iOS ignora il manifest per lo schermo intero: servono i suoi meta. */ ?>
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Colombini">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
