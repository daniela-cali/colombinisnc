<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Accedi<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-wrapper">
    <div class="login-card card shadow">

        <div class="login-card-header">
            <i class="bi bi-water login-logo-icon"></i>
            <span class="login-brand"><strong>Colombini</strong> SNC</span>
        </div>

        <div class="card-body">
            <p class="text-center text-muted mb-4">Accedi per continuare</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger py-2">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif ?>

            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger py-2">
                    <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
                        <div><?= esc($e) ?></div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <?php if (isset($errors) && $errors): ?>
                <div class="alert alert-danger py-2">
                    <?php foreach ($errors as $error): ?>
                        <div><?= esc($error) ?></div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <form action="<?= route_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="input-group mb-3">
                    <input type="text"
                           name="username"
                           class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                           placeholder="Nome utente"
                           value="<?= old('username') ?>"
                           autocomplete="username"
                           autofocus>
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                </div>

                <div class="input-group mb-3">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Password"
                           autocomplete="current-password">
                    <button type="button" class="input-group-text" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label fw-semibold" for="remember">Ricordami</label>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Accedi</button>
                </div>

            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
<?= $this->endSection() ?>
