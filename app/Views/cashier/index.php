<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Auth.cashierHome')) ?></h1>
        <p class="text-muted mb-0">
            <?= esc(lang('Auth.signedInAs')) ?> <strong><?= esc($user->username) ?></strong>
        </p>
    </div>

    <form method="post" action="/logout" class="mb-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-primary"><?= esc(lang('Auth.logoutButton')) ?></button>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p class="mb-0"><?= esc(lang('Auth.cashierHome')) ?></p>
    </div>
</div>
<?= $this->endSection() ?>
