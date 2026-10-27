<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Sale.listTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Sale.listHeading')) ?></p>
    </div>
    <a href="/admin/sales/create" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> <?= esc(lang('Sale.createTitle')) ?>
    </a>
</div>

<!-- Resumen del período -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0"><?= $summary ? (int) $summary->total_sales : 0 ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.totalSales')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success mb-0">Bs <?= $summary ? number_format((float) $summary->total_revenue, 2, ',', '.') : '0,00' ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.totalRevenue')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info mb-0"><?= isset($summary->by_method) ? count((array) $summary->by_method) : 0 ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.byMethod')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <a href="/admin/sales/daily-report?date=<?= date('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-calendar-day mr-1"></i> <?= esc(lang('Sale.dailyReportTitle')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-2">
                <label class="form-label small"><?= esc(lang('Sale.dateFrom')) ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><?= esc(lang('Sale.dateTo')) ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><?= esc(lang('Sale.status')) ?></label>
                <select name="status" class="form-control">
                    <option value=""><?= esc(lang('Sale.allStatus')) ?></option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>><?= esc(lang('Sale.pending')) ?></option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>><?= esc(lang('Sale.completed')) ?></option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>><?= esc(lang('Sale.cancelled')) ?></option>
                    <option value="refunded" <?= ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' ?>><?= esc(lang('Sale.refunded')) ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-filter mr-1"></i> <?= esc(lang('Sale.filter')) ?>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <div>
                    <a href="/admin/sales" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times mr-1"></i> <?= esc(lang('Sale.clearFilters')) ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (session('success')) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php } ?>

<?php if (session('errors')) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach (session('errors') as $error) { ?>
                <li><?= esc($error) ?></li>
            <?php } ?>
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php } ?>

<!-- Tabla de ventas -->
<div class="card">
    <div class="card-body p-5">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="salesTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th><?= esc(lang('Sale.date')) ?></th>
                        <th><?= esc(lang('Sale.cashier')) ?></th>
                        <th class="text-right"><?= esc(lang('Sale.total')) ?></th>
                        <th><?= esc(lang('Sale.paymentMethod')) ?></th>
                        <th class="text-center"><?= esc(lang('Sale.status')) ?></th>
                        <th class="text-center" style="width: 80px;"><?= esc(lang('Product.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)) {
                        echo '<tr data-tableempty="true"><td colspan="7">No hay ventas en el período seleccionado.</td></tr>';
                    } else { ?>
                        <?php foreach ($sales as $sale) { ?>
                            <tr>
                                <td><?= esc($sale->id) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($sale->created_at)) ?></td>
                                <td><?= esc($sale->cashier_name ?? '—') ?></td>
                                <td class="text-right font-weight-bold">Bs <?= number_format((float) $sale->total_amount, 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= esc(lang('Sale.'.$sale->payment_method)) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusClass = [
                                        'pending' => 'badge-warning',
                                        'completed' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'refunded' => 'badge-secondary',
                                    ];
                            $cls = $statusClass[$sale->status] ?? 'badge-secondary';
                            ?>
                                    <span class="badge <?= $cls ?>">
                                        <?= esc(lang('Sale.'.$sale->status)) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="/admin/sales/show/<?= $sale->id ?>" class="btn btn-outline-primary btn-sm" title="<?= esc(lang('Sale.detailTitle')) ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($pager->getPageCount() > 1) { ?>
        <div class="card-footer">
            <?= $pager->links('default', 'bootstrap_full') ?>
        </div>
    <?php } ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#salesTable').DataTable({
                language: { url: '/assets/vendor/datatables/1.13.11/Spanish.json' },
                pageLength: 20,
                order: [[1, 'desc']],
                columnDefs: [{ orderable: false, targets: [6] }]
            });
        }
    });
</script>
<?= $this->endSection() ?>
