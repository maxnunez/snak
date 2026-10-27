<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Category.listTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Category.listHeading')) ?></p>
    </div>
    <a href="/admin/categories/create" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> <?= esc(lang('Category.createNew')) ?>
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
    <div class="card-body p-5">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="categoriesTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th><?= esc(lang('Category.name')) ?></th>
                        <th><?= esc(lang('Category.slug')) ?></th>
                        <th class="text-center" style="width: 80px;"><?= esc(lang('Category.order')) ?></th>
                        <th class="text-center" style="width: 100px;"><?= esc(lang('Category.status')) ?></th>
                        <th class="text-center" style="width: 120px;"><?= esc(lang('Category.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)):
                        echo '<tr data-tableempty="true"><td colspan="6">No hay categorías registradas.</td></tr>';
                    else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= esc($category->id) ?></td>
                                <td><strong><?= esc($category->name) ?></strong></td>
                                <td><code><?= esc($category->slug) ?></code></td>
                                <td class="text-center"><?= esc($category->sort_order) ?></td>
                                <td class="text-center">
                                    <?php if ((int)$category->is_active === 1): ?>
                                        <span class="badge badge-success"><?= esc(lang('Category.active')) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= esc(lang('Category.inactive')) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/admin/categories/edit/<?= $category->id ?>" class="btn btn-outline-primary" title="<?= esc(lang('Category.edit')) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ((int)$category->is_active === 1): ?>
                                            <form method="post" action="/admin/categories/delete/<?= $category->id ?>" class="d-inline" onsubmit="return confirm('<?= esc(lang('Category.confirmDelete')) ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="<?= esc(lang('Category.delete')) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="/admin/categories/delete/<?= $category->id ?>" class="d-inline" onsubmit="return confirm('<?= esc(lang('Category.confirmActivate')) ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-success" title="<?= esc(lang('Category.activate')) ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#categoriesTable').DataTable({
                language: {
                    url: '/assets/vendor/datatables/1.13.11/Spanish.json'
                },
                pageLength: 20,
                order: [[3, 'asc'], [1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [5] }
                ]
            });
        }
    });
</script>
<?= $this->endSection() ?>
