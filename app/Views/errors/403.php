<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <h1 class="h4 text-primary mb-3">403</h1>
                <p class="mb-4"><?= esc(lang('Auth.forbidden')) ?></p>
                <a href="/" class="btn btn-primary" style="background-color:#003366;border-color:#003366;">
                    <?= esc(lang('Auth.backHome')) ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
