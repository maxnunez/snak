<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Sale.detailTitle')) ?> #<?= esc($sale->id) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Sale.listHeading')) ?></p>
    </div>
    <a href="/admin/sales" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> <?= esc(lang('Sale.backToList')) ?>
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0">#<?= esc($sale->id) ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.detailTitle')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success mb-0">Bs <?= number_format((float)$sale->total_amount, 2, ',', '.') ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.total')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info mb-0"><?= count($sale->items) ?></h3>
                <small class="text-muted"><?= esc(lang('Sale.items')) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <span class="badge badge-<?= ['pending' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary'][$sale->status] ?? 'secondary' ?> fs-6">
                    <?= esc(lang('Sale.' . $sale->status)) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= esc(lang('Sale.info')) ?></h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4"><?= esc(lang('Sale.date')) ?></dt>
                    <dd class="col-sm-8"><?= date('d/m/Y H:i:s', strtotime($sale->created_at)) ?></dd>
                    <dt class="col-sm-4"><?= esc(lang('Sale.cashier')) ?></dt>
                    <dd class="col-sm-8"><?= esc($sale->cashier_name ?? '—') ?></dd>
                    <dt class="col-sm-4"><?= esc(lang('Sale.paymentMethod')) ?></dt>
                    <dd class="col-sm-8"><span class="badge badge-info"><?= esc(lang('Sale.' . $sale->payment_method)) ?></span></dd>
                    <dt class="col-sm-4"><?= esc(lang('Sale.status')) ?></dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-<?= ['pending' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary'][$sale->status] ?? 'secondary' ?>">
                            <?= esc(lang('Sale.' . $sale->status)) ?>
                        </span>
                    </dd>
                    <?php if (!empty($sale->notes)): ?>
                        <dt class="col-sm-4"><?= esc(lang('Sale.notes')) ?></dt>
                        <dd class="col-sm-8"><?= esc($sale->notes) ?></dd>
                    <?php endif ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= esc(lang('Sale.items')) ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= esc(lang('Sale.product')) ?></th>
                                <th class="text-center"><?= esc(lang('Sale.quantity')) ?></th>
                                <th class="text-right"><?= esc(lang('Sale.unitPrice')) ?></th>
                                <th class="text-right"><?= esc(lang('Sale.subtotal')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sale->items as $item): ?>
                                <tr>
                                    <td><strong><?= esc($item->product_name ?? 'Producto eliminado') ?></strong></td>
                                    <td class="text-center"><?= esc($item->quantity) ?></td>
                                    <td class="text-right">Bs <?= number_format((float)$item->unit_price, 2, ',', '.') ?></td>
                                    <td class="text-right font-weight-bold">Bs <?= number_format((float)$item->subtotal, 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="3" class="text-right"><?= esc(lang('Sale.total')) ?></td>
                                <td class="text-right">Bs <?= number_format((float)$sale->total_amount, 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
