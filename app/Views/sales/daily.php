<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Sale.dailyReportTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Sale.dailyReportHeading')) ?>: <?= date('d/m/Y', strtotime($date)) ?></p>
    </div>
    <div>
        <a href="/admin/sales/daily-report?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="btn btn-outline-secondary btn-sm mr-2">
            <i class="fas fa-chevron-left mr-1"></i> <?= esc(lang('Sale.previous')) ?>
        </a>
        <a href="/admin/sales/daily-report?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="btn btn-outline-secondary btn-sm mr-2">
            <?= esc(lang('Sale.next')) ?> <i class="fas fa-chevron-right ml-1"></i>
        </a>
        <a href="/admin/sales/daily-report?date=<?= date('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-calendar-day mr-1"></i> <?= esc(lang('Sale.today')) ?>
        </a>
    </div>
</div>

<!-- Resumen del día -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0"><?= (int)$summary['total_sales'] ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.totalSales')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success mb-0">Bs <?= number_format((float)$summary['total_revenue'], 2, ',', '.') ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.totalRevenue')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info mb-0"><?= count($summary['by_method'] ?? []) ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.byMethod')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <a href="/admin/sales/top-products?date_from=<?= $date ?>&date_to=<?= $date ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-trophy mr-1"></i> <?= esc(lang('Cashier.topProducts')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ventas por método de pago -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= esc(lang('Sale.byMethod')) ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($summary['by_method'])): ?>
                    <p class="text-center text-muted mb-0"><?= esc(lang('Sale.noSales')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?= esc(lang('Sale.paymentMethod')) ?></th>
                                    <th class="text-center"><?= esc(lang('Sale.totalSales')) ?></th>
                                    <th class="text-right"><?= esc(lang('Sale.revenue')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary['by_method'] as $method => $data): ?>
                                    <tr>
                                        <td><span class="badge badge-info"><?= esc(lang('Sale.' . $method)) ?></span></td>
                                        <td class="text-center font-weight-bold"><?= (int)$data['count'] ?></td>
                                        <td class="text-right font-weight-bold text-success">Bs <?= number_format((float)$data['revenue'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td><?= esc(lang('Sale.total')) ?></td>
                                    <td class="text-center"><?= (int)$summary['total_sales'] ?></td>
                                    <td class="text-right text-success">Bs <?= number_format((float)$summary['total_revenue'], 2, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= esc(lang('Sale.salesOfDay')) ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="dailySalesTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th><?= esc(lang('Sale.time')) ?></th>
                                <th><?= esc(lang('Sale.cashier')) ?></th>
                                <th class="text-right"><?= esc(lang('Sale.total')) ?></th>
                                <th><?= esc(lang('Sale.paymentMethod')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)):
                                echo '<tr data-tableempty="true"><td colspan="5">No hay ventas registradas para esta fecha.</td></tr>';
                            else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>#<?= esc($sale->id) ?></td>
                                        <td><?= date('H:i', strtotime($sale->created_at)) ?></td>
                                        <td><?= esc($sale->cashier_name ?? '—') ?></td>
                                        <td class="text-right font-weight-bold">Bs <?= number_format((float)$sale->total_amount, 2, ',', '.') ?></td>
                                        <td><span class="badge badge-info"><?= esc(lang('Sale.' . $sale->payment_method)) ?></span></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de ventas -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?= esc(lang('Sale.productDetail')) ?></h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dailyItemsTable">
                <thead>
                    <tr>
                        <th><?= esc(lang('Sale.product')) ?></th>
                        <th class="text-center"><?= esc(lang('Sale.quantity')) ?></th>
                        <th class="text-right"><?= esc(lang('Sale.revenue')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $itemsByProduct = [];
                    foreach ($sales as $sale) {
                        foreach ($sale->items ?? [] as $item) {
                            $key = $item->product_id ?? 0;
                            if (!isset($itemsByProduct[$key])) {
                                $itemsByProduct[$key] = ['name' => $item->product_name ?? 'Producto eliminado', 'qty' => 0, 'revenue' => 0];
                            }
                            $itemsByProduct[$key]['qty'] += (int)$item->quantity;
                            $itemsByProduct[$key]['revenue'] += (float)$item->subtotal;
                        }
                    }
                    ?>
                    <?php if (empty($itemsByProduct)):
                        echo '<tr data-tableempty="true"><td colspan="3">No hay productos vendidos en esta fecha.</td></tr>';
                    else: ?>
                        <?php foreach ($itemsByProduct as $item): ?>
                            <tr>
                                <td><?= esc($item['name']) ?></td>
                                <td class="text-center font-weight-bold"><?= (int)$item['qty'] ?></td>
                                <td class="text-right font-weight-bold text-success">Bs <?= number_format($item['revenue'], 2, ',', '.') ?></td>
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
            $('#dailySalesTable').DataTable({ language: { url: '/assets/vendor/datatables/1.13.11/Spanish.json' }, pageLength: 15, order: [[1, 'asc']], columnDefs: [{ orderable: false, targets: [] }] });
            $('#dailyItemsTable').DataTable({ language: { url: '/assets/vendor/datatables/1.13.11/Spanish.json' }, pageLength: 20, order: [[1, 'desc']], columnDefs: [{ orderable: false, targets: [] }] });
        }
    });
</script>
<?= $this->endSection() ?>
