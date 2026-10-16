<?php
/**
 * @var string $smtp_host
 * @var int    $smtp_porta
 * @var string $smtp_cifratura ssl o tls
 * @var string $email_mittente nome dell'azienda nel mittente, prima del nome dell'operatore
 * @var string $oggetto_email  oggetto della proposta, con il segnaposto {anno}
 * @var string $testo_email    testo della proposta, con il segnaposto {anno}
 * @var int    $invii_massimi  proposte al massimo per invio multiplo
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Email<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Email</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>
<form method="post" action="<?= base_url('impostazioni/email') ?>">
    <?= csrf_field() ?>
    <div class="row g-4 align-items-start">

        <div class="col-lg-5">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-hdd-network me-2"></i>Server di posta</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uguale per tutti. Ogni operatore spedisce dalla propria casella, con la password
                        e la firma che inserisce nel suo profilo.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Server SMTP</label>
                        <input type="text" name="smtp_host" class="form-control" required
                               value="<?= esc(old('smtp_host', $smtp_host, false)) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Porta</label>
                            <input type="number" name="smtp_porta" class="form-control" min="1" max="65535" required
                                   value="<?= esc(old('smtp_porta', (string) $smtp_porta, false)) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cifratura</label>
                            <?php $cifratura = old('smtp_cifratura', $smtp_cifratura); ?>
                            <select name="smtp_cifratura" class="form-select" required>
                                <option value="ssl" <?= $cifratura === 'ssl' ? 'selected' : '' ?>>SSL (porta 465)</option>
                                <option value="tls" <?= $cifratura === 'tls' ? 'selected' : '' ?>>STARTTLS (porta 587)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Nome nel mittente</label>
                        <input type="text" name="email_mittente" class="form-control" required
                               value="<?= esc(old('email_mittente', $email_mittente, false)) ?>">
                        <div class="form-text">
                            Il cliente vede «<?= esc($email_mittente) ?> - Nome Cognome» dell'operatore.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Proposta di abbonamento</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        L'email con cui si spedisce la proposta in PDF. <code>{anno}</code> diventa l'anno di
                        inizio dell'abbonamento; la firma dell'operatore si aggiunge da sola in fondo.
                        Prima di ogni invio l'operatore può ancora correggere oggetto e testo.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Oggetto</label>
                        <input type="text" name="oggetto_email" class="form-control" required maxlength="200"
                               value="<?= esc(old('oggetto_email', $oggetto_email, false)) ?>">
                    </div>
                    <div>
                        <label class="form-label">Testo</label>
                        <textarea name="testo_email" class="form-control" rows="9" required><?= esc(old('testo_email', $testo_email, false)) ?></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Invii massimi per volta</label>
                        <input type="number" name="invii_massimi" class="form-control" min="1" max="200" required
                               value="<?= esc(old('invii_massimi', (string) $invii_massimi, false)) ?>">
                        <div class="form-text">
                            Quante proposte si possono selezionare per un invio multiplo. Ogni proposta conta
                            due destinatari (cliente e copia nascosta): tienilo sotto la soglia di invio
                            del vostro piano Aruba, oltre la quale la casella viene bloccata.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card-azioni mt-4 mb-3">
        <a href="<?= base_url('impostazioni') ?>"
           class="btn btn-sm btn-outline-secondary azione-ritorno">
            <i class="bi bi-arrow-left me-1"></i>Indietro
        </a>
        <button type="submit" class="btn btn-sm btn-primary azione-primaria">
            <i class="bi bi-check-lg me-1"></i>Salva impostazioni
        </button>
    </div>
</form>
<?= $this->endSection() ?>
