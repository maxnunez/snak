<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Sale.topProductsTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Sale.topProductsHeading')) ?></p>
    </div>
    <a href="/admin/sales" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> <?= esc(lang('Sale.backToList')) ?>
    </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small"><?= esc(lang('Sale.dateFrom')) ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small"><?= esc(lang('Sale.dateTo')) ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><?= esc(lang('Sale.limit')) ?></label>
                <select name="limit" class="form-control">
                    <option value="10" <?= ($filters['limit'] ?? 20) == 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= ($filters['limit'] ?? 20) == 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= ($filters['limit'] ?? 20) == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($filters['limit'] ?? 20) == 100 ? 'selected' : '' ?>>100</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter mr-1"></i> <?= esc(lang('Sale.filter')) ?>
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <a href="/admin/sales/top-products" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times mr-1"></i> <?= esc(lang('Sale.clearFilters')) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif ?>

<!-- Ranking -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="topProductsTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th><?= esc(lang('Sale.product')) ?></th>
                        <th class="text-center"><?= esc(lang('Sale.quantity')) ?></th>
                        <th class="text-right"><?= esc(lang('Sale.revenue')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)):
                        echo '<tr data-tableempty="true"><td colspan="4">No hay ventas en el período seleccionado.</td></tr>';
                    else: ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($topProducts as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?= $rank <= 3 ? 'warning' : 'secondary' ?> fs-6">#<?= $rank++ ?></span>
                                </td>
                                <td><strong><?= esc($item->name) ?></strong></td>
                                <td class="text-center font-weight-bold text-primary"><?= (int)$item->total_qty ?></td>
                                <td class="text-right font-weight-bold text-success">Bs <?= number_format((float)$item->total_revenue, 2, ',', '.') ?></td>
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
            $('#topProductsTable').DataTable({ language: { url: '/assets/vendor/datatables/1.13.11/Spanish.json' }, pageLength: 20, order: [[2, 'desc']], columnDefs: [{ orderable: false, targets: [0] }] });
        }
    });
</script>
<?= $this->endSection() ?>
