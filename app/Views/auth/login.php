<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<?php $errors = $errors ?? []; ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 text-primary mb-1"><?= esc(lang('Auth.loginTitle')) ?></h1>
                <p class="text-muted mb-4"><?= esc(lang('Auth.loginHeading')) ?></p>

                <form method="post" action="/login" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="username"><?= esc(lang('Auth.usernameLabel')) ?></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>"
                            id="username"
                            name="username"
                            value="<?= esc(old('username') ?? '') ?>"
                            autocomplete="username"
                            autofocus
                        >
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['username']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="password"><?= esc(lang('Auth.passwordLabel')) ?></label>
                        <input
                            type="password"
                            class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                        >
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['password']) ?></div>
                        <?php endif ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="background-color:#003366;border-color:#003366;">
                        <?= esc(lang('Auth.loginButton')) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<?php if (! empty($alert)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: <?= json_encode($alert) ?>,
        confirmButtonColor: '#003366'
    });
</script>
<?php endif ?>
<?= $this->endSection() ?>
