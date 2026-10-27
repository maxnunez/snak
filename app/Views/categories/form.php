<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1">
            <?= $category ? esc(lang('Category.editTitle')) : esc(lang('Category.createTitle')) ?>
        </h1>
        <p class="text-muted mb-0">
            <?= $category ? esc(lang('Category.editHeading')) : esc(lang('Category.createHeading')) ?>
        </p>
    </div>
    <a href="/admin/categories" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> <?= esc(lang('Category.backToList')) ?>
    </a>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif ?>

<?php if (session('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach (session('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $category ? '/admin/categories/update/' . $category->id : '/admin/categories/store' ?>" novalidate>
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name"><?= esc(lang('Category.name')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                            id="name"
                            name="name"
                            value="<?= esc(old('name', $category->name ?? '')) ?>"
                            maxlength="100"
                            required
                            autofocus
                        >
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['name']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="slug"><?= esc(lang('Category.slug')) ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">/</span>
                            </div>
                            <input
                                type="text"
                                class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>"
                                id="slug"
                                name="slug"
                                value="<?= esc(old('slug', $category->slug ?? '')) ?>"
                                maxlength="120"
                                required
                            >
                        </div>
                        <?php if (isset($errors['slug'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['slug']) ?></div>
                        <?php endif ?>
                        <small class="form-text text-muted"><?= esc(lang('Category.slugHelp')) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="description"><?= esc(lang('Category.description')) ?></label>
                        <textarea
                            class="form-control"
                            id="description"
                            name="description"
                            rows="3"
                            maxlength="500"
                        ><?= esc(old('description', $category->description ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sort_order"><?= esc(lang('Category.sortOrder')) ?></label>
                        <input
                            type="number"
                            class="form-control"
                            id="sort_order"
                            name="sort_order"
                            value="<?= esc(old('sort_order', $category->sort_order ?? 0)) ?>"
                            min="0"
                        >
                        <small class="form-text text-muted"><?= esc(lang('Category.sortOrderHelp')) ?></small>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="is_active"
                                name="is_active"
                                value="1"
                                <?= (old('is_active', $category->is_active ?? 1) == 1) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="is_active">
                                <?= esc(lang('Category.active')) ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end">
                <a href="/admin/categories" class="btn btn-outline-secondary mr-2">
                    <?= esc(lang('Category.cancel')) ?>
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    <?= $category ? esc(lang('Category.update')) : esc(lang('Category.create')) ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // Auto-generate slug from name
        $('#name').on('blur', function() {
            if (!$('#slug').val() || $('#slug').data('auto')) {
                var slug = $(this).val()
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $('#slug').val(slug).data('auto', true);
            }
        });

        $('#slug').on('input', function() {
            $(this).data('auto', false);
        });
    });
</script>
<?= $this->endSection() ?>
