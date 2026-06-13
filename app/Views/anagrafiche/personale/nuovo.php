<?php
/**
 * @var array $gruppi
 * @var array $pastelli
 * @var array $colori_usati
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Nuovo Dipendente<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/personale') ?>">Personale</a></li>
        <li class="breadcrumb-item active">Nuovo</li>
    </ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-person-plus me-2"></i>Nuovo dipendente</h3>
            </div>
            <form action="<?= base_url('anagrafiche/personale/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">

                    <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Anagrafica</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control"
                                   value="<?= esc(old('nome')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cognome <span class="text-danger">*</span></label>
                            <input type="text" name="cognome" class="form-control"
                                   value="<?= esc(old('cognome')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="<?= esc(old('telefono')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Colore profilo</label>
                            <?php $coloreCorrente = old('colore', '#ee8686'); ?>
                            <input type="hidden" name="colore" id="coloreInput" value="<?= esc($coloreCorrente) ?>">
                            <div class="d-flex align-items-center gap-3 mb-2 mt-1">
                                <div id="colorePreview" class="color-swatch" style="background-color:<?= esc($coloreCorrente) ?>;border:2px solid #dee2e6;cursor:default;"></div>
                                <input type="range" id="hueSlider" min="0" max="360" value="0" class="hue-slider flex-grow-1">
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-1">
                                <?php foreach ($pastelli as $hex):
                                    $taken = in_array($hex, $colori_usati);
                                ?>
                                    <div class="color-swatch <?= $hex === $coloreCorrente ? 'selected' : '' ?> <?= $taken ? 'taken' : '' ?>"
                                         style="background-color:<?= esc($hex) ?>"
                                         data-color="<?= esc($hex) ?>"
                                         title="<?= $taken ? 'Già assegnato' : '' ?>"></div>
                                <?php endforeach ?>
                            </div>
                            <span class="text-muted small">Scorciatoie predefinite — o scegli una tinta con lo slider</span>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-key me-1"></i> Account di accesso</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= esc(old('username')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= esc(old('email')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conferma password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>

                    <p class="text-muted section-header mb-3"><i class="bi bi-shield me-1"></i> Gruppi <span class="text-danger">*</span></p>
                    <div class="row g-2">
                        <?php foreach ($gruppi as $key => $label): ?>
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="gruppi[]" value="<?= esc($key) ?>"
                                           id="gruppo_<?= esc($key) ?>"
                                           <?= in_array($key, (array) old('gruppi', [])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="gruppo_<?= esc($key) ?>">
                                        <?= esc($label) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= base_url('anagrafiche/personale') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const S = 75, L = 73;

    function hslToHex(h) {
        const s = S / 100, l = L / 100;
        const k = n => (n + h / 30) % 12;
        const a = s * Math.min(l, 1 - l);
        const f = n => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
        return '#' + [f(0), f(8), f(4)].map(x => Math.round(x * 255).toString(16).padStart(2, '0')).join('');
    }

    function hexToHue(hex) {
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
        if (d === 0) return 0;
        let h;
        if (max === r)      h = ((g - b) / d + (g < b ? 6 : 0));
        else if (max === g) h = (b - r) / d + 2;
        else                h = (r - g) / d + 4;
        return Math.round(h * 60);
    }

    const slider  = document.getElementById('hueSlider');
    const preview = document.getElementById('colorePreview');
    const input   = document.getElementById('coloreInput');
    const swatches = document.querySelectorAll('.color-swatch[data-color]');

    slider.value = hexToHue(input.value);

    function applyColor(hex) {
        input.value = hex;
        preview.style.backgroundColor = hex;
    }

    function selectSwatch(hex) {
        swatches.forEach(s => s.classList.remove('selected'));
        swatches.forEach(s => { if (s.dataset.color === hex) s.classList.add('selected'); });
    }

    slider.addEventListener('input', function () {
        const hex = hslToHex(parseInt(this.value));
        applyColor(hex);
        swatches.forEach(s => s.classList.remove('selected'));
    });

    swatches.forEach(function (swatch) {
        if (swatch.dataset.color === input.value) swatch.classList.add('selected');
        swatch.addEventListener('click', function () {
            if (this.classList.contains('taken')) return;
            selectSwatch(this.dataset.color);
            slider.value = hexToHue(this.dataset.color);
            applyColor(this.dataset.color);
        });
    });
})();
</script>
<?= $this->endSection() ?>
