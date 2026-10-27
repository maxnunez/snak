<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Info boxes -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= $stats['total_products'] ?? 0 ?></h3>
                    <p>Productos Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-box"></i>
                </div>
                <a href="/admin/products" class="small-box-footer">
                    Más info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 style="font-size: 2.2rem;"><?= $stats['low_stock'] ?? 0 ?></h3>
                    <p>Stock Bajo</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="/admin/products?stock=low" class="small-box-footer">
                    Ver productos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Bs&nbsp;<?= number_format((float) ($stats['today_revenue'] ?? 0), 2, ',', '.') ?></h3>
                    <p>Ventas Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <a href="/admin/sales/daily-report?date=<?= date('Y-m-d') ?>" class="small-box-footer">
                    Reporte diario <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 style="font-size: 2.2rem;"><?= $stats['today_sales'] ?? 0 ?></h3>
                    <p>Transacciones Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <a href="/admin/sales" class="small-box-footer">
                    Ver ventas <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- /.row -->

    <!-- Main row -->
    <div class="row">
        <!-- Acciones rápidas + Stock bajo -->
        <div class="col-md-6">
            <!-- Acciones Rápidas -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt mr-1"></i> Acciones Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1">
                                    <i class="fas fa-cash-register"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Nueva Venta</span>
                                    <a href="/admin/sales/create" class="btn btn-sm btn-primary mt-1">
                                        <i class="fas fa-plus mr-1"></i> Registrar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success elevation-1">
                                    <i class="fas fa-box"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Nuevo Producto</span>
                                    <a href="/admin/products/create" class="btn btn-sm btn-success mt-1">
                                        <i class="fas fa-plus mr-1"></i> Crear
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Nueva Categoría</span>
                                    <a href="/admin/categories/create" class="btn btn-sm btn-warning mt-1">
                                        <i class="fas fa-plus mr-1"></i> Crear
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1">
                                    <i class="fas fa-calendar-day"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Reporte Diario</span>
                                    <a href="/admin/sales/daily-report?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-info mt-1">
                                        <i class="fas fa-chart-bar mr-1"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->

        <!-- Stock Bajo -->
        <div class="col-md-6">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Stock Bajo
                    </h3>
                    <?php if (! empty($lowStock)) { ?>
                        <div class="card-tools">
                            <a href="/admin/products?stock=low" class="btn btn-tool btn-warning">
                                <i class="fas fa-list"></i> Ver todos
                            </a>
                        </div>
                    <?php } ?>
                </div>
                <?php if (empty($lowStock)) { ?>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">¡Todos los productos tienen stock suficiente!</p>
                    </div>
                <?php } else { ?>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($lowStock, 0, 10) as $product) { ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($product->name) ?></strong>
                                            <?php if ((int) $product->stock === 0) { ?>
                                                <span class="badge badge-danger ml-1">Sin stock</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-<?= (int) $product->stock === 0 ? 'danger' : 'warning' ?>">
                                                <?= esc($product->stock) ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= esc($product->min_stock) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->

    <!-- Ventas recientes -->
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-1"></i> Ventas Recientes
                    </h3>
                    <div class="card-tools">
                        <a href="/admin/sales" class="btn btn-tool btn-primary">
                            <i class="fas fa-list"></i> Ver todas
                        </a>
                    </div>
                </div>
                <div class="card-body p-5">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Fecha</th>
                                    <th>Cajero</th>
                                    <th class="text-right">Total</th>
                                    <th>Método</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentSales)) { ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay ventas recientes.
                                        </td>
                                    </tr>
                                <?php } else { ?>
                                    <?php foreach ($recentSales as $sale) { ?>
                                        <tr>
                                            <td><span class="badge badge-info">#<?= esc($sale->id) ?></span></td>
                                            <td><?= date('d/m/Y H:i', strtotime($sale->created_at)) ?></td>
                                            <td>
                                                <i class="fas fa-user mr-1 text-muted"></i>
                                                <?= esc($sale->cashier_name ?? '—') ?>
                                            </td>
                                            <td class="text-right font-weight-bold">Bs <?= number_format((float) $sale->total_amount, 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <i class="fas <?= [
                                                        'cash' => 'fa-money-bill-wave',
                                                        'card' => 'fa-credit-card',
                                                        'transfer' => 'fa-exchange-alt',
                                                        'qr' => 'fa-qrcode',
                                                    ][$sale->payment_method] ?? 'fa-money-bill' ?> mr-1"></i>
                                                    <?= esc(lang('Sale.'.$sale->payment_method)) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $statusConfig = [
                                                    'pending' => ['badge' => 'badge-warning', 'icon' => 'fa-clock', 'text' => 'Pendiente'],
                                                    'completed' => ['badge' => 'badge-success', 'icon' => 'fa-check-circle', 'text' => 'Completada'],
                                                    'cancelled' => ['badge' => 'badge-danger', 'icon' => 'fa-times-circle', 'text' => 'Cancelada'],
                                                    'refunded' => ['badge' => 'badge-secondary', 'icon' => 'fa-undo', 'text' => 'Reembolsada'],
                                                ];
                                        $cfg = $statusConfig[$sale->status] ?? $statusConfig['pending'];
                                        ?>
                                                <span class="badge <?= $cfg['badge'] ?>">
                                                    <i class="fas <?= $cfg['icon'] ?> mr-1"></i>
                                                    <?= esc(lang('Sale.'.$sale->status)) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
    </div>
    <!-- /.row -->

</div>
<!-- /.container-fluid -->
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // Ventas recientes: tabla estática sin DataTables
        // (solo muestra últimas 10, no necesita paginación)
    });
</script>
<?= $this->endSection() ?>
