<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 text-primary mb-2"><?= esc(lang('App.homeTitle')) ?></h1>
        <p class="mb-0"><?= esc(lang('App.welcome')) ?></p>
    </div>
</div>
<?= $this->endSection() ?>
