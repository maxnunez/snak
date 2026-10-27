<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Order.title')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Order.subtitle')) ?></p>
    </div>
    <a href="/admin/products" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> <?= esc(lang('Product.backToList')) ?>
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

<div class="row">
    <!-- Formulario de pedido -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2"></i><?= esc(lang('Order.newOrder')) ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="/orders/store" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="student_name"><?= esc(lang('Order.studentName')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['student_name']) ? ' is-invalid' : '' ?>"
                            id="student_name"
                            name="student_name"
                            value="<?= esc(old('student_name')) ?>"
                            maxlength="100"
                            required
                            autofocus
                            placeholder="<?= esc(lang('Order.studentNamePlaceholder')) ?>"
                        >
                        <?php if (isset($errors['student_name'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['student_name']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="student_lastname"><?= esc(lang('Order.studentLastname')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['student_lastname']) ? ' is-invalid' : '' ?>"
                            id="student_lastname"
                            name="student_lastname"
                            value="<?= esc(old('student_lastname')) ?>"
                            maxlength="100"
                            required
                            placeholder="<?= esc(lang('Order.studentLastnamePlaceholder')) ?>"
                        >
                        <?php if (isset($errors['student_lastname'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['student_lastname']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="student_code"><?= esc(lang('Order.studentCode')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['student_code']) ? ' is-invalid' : '' ?>"
                            id="student_code"
                            name="student_code"
                            value="<?= esc(old('student_code')) ?>"
                            maxlength="4"
                            required
                            placeholder="0000"
                            inputmode="numeric"
                        >
                        <?php if (isset($errors['student_code'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['student_code']) ?></div>
                        <?php endif ?>
                        <small class="form-text text-muted"><?= esc(lang('Order.studentCodeHelp')) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="student_initials"><?= esc(lang('Order.studentInitials')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['student_initials']) ? ' is-invalid' : '' ?>"
                            id="student_initials"
                            name="student_initials"
                            value="<?= esc(old('student_initials')) ?>"
                            maxlength="10"
                            required
                            placeholder="JD-"
                        >
                        <?php if (isset($errors['student_initials'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['student_initials']) ?></div>
                        <?php endif ?>
                        <small class="form-text text-muted"><?= esc(lang('Order.studentInitialsHelp')) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="product_id"><?= esc(lang('Order.product')) ?> <span class="text-danger">*</span></label>
                        <select class="form-control<?= isset($errors['product_id']) ? ' is-invalid' : '' ?>" id="product_id" name="product_id" required>
                            <option value=""><?= esc(lang('Order.selectProduct')) ?></option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product->id ?>" <?= (old('product_id') == $product->id) ? 'selected' : '' ?>>
                                    <?= esc($product->name) ?> - Bs <?= number_format((float)$product->price, 2, ',', '.') ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <?php if (isset($errors['product_id'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['product_id']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> <?= esc(lang('Order.registerOrder')) ?>
                        </button>
                        <button type="reset" class="btn btn-outline-secondary"><?= esc(lang('Order.clear')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de productos disponibles -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-box mr-2"></i><?= esc(lang('Order.availableProducts')) ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($products)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-box-open fa-2x mb-2"></i><br>
                        <?= esc(lang('Order.noProductsAvailable')) ?>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($products as $product): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= esc($product->name) ?></strong><br>
                                    <small class="text-muted">
                                        Bs <?= number_format((float)$product->price, 2, ',', '.') ?>
                                        <?php if (!empty($product->category_name)): ?>
                                            | <?= esc($product->category_name) ?>
                                        <?php endif ?>
                                    </small>
                                </div>
                                <span class="badge badge-success"><?= (int)$product->stock ?> <?= esc(lang('Order.available')) ?></span>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Pedidos registrados -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-clipboard-list mr-2"></i><?= esc(lang('Order.recentOrders')) ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        <?= esc(lang('Order.noOrdersYet')) ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?= esc(lang('Order.student')) ?></th>
                                    <th class="text-center"><?= esc(lang('Order.code')) ?></th>
                                    <th><?= esc(lang('Order.product')) ?></th>
                                    <th class="text-center"><?= esc(lang('Order.status')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($order->student_initials) . ' ' . esc($order->student_name) . ' ' . esc($order->student_lastname) ?></strong><br>
                                            <small class="text-muted"><?= esc($order->student_code) ?></small>
                                        </td>
                                        <td class="text-center"><?= esc($order->student_code) ?></td>
                                        <td><?= esc($order->product_name ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php
                                            $badgeClass = [
                                                'pending'   => 'badge-warning',
                                                'completed' => 'badge-success',
                                                'cancelled' => 'badge-danger',
                                            ];
                                            $cls = $badgeClass[$order->status] ?? 'badge-secondary';
                                            ?>
                                            <span class="badge <?= $cls ?>">
                                                <?= esc(lang('Order.' . $order->status)) ?>
                                            </span>
                                            <?php if ($order->status === 'pending'): ?>
                                                <br>
                                                <a href="/orders/complete/<?= $order->id ?>" class="btn btn-sm btn-success" onclick="return confirm('<?= esc(lang('Order.confirmComplete')) ?>')"><i class="fas fa-check"></i></a>
                                                <a href="/orders/cancel/<?= $order->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= esc(lang('Order.confirmCancel')) ?>')"><i class="fas fa-times"></i></a>
                                            <?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // Auto-generar iniciales al escribir nombre y apellido
        function updateInitials() {
            var name = $('#student_name').val() || '';
            var lastname = $('#student_lastname').val() || '';
            if (name.trim() !== '' && lastname.trim() !== '') {
                var initials = name.trim().charAt(0).toUpperCase() + lastname.trim().charAt(0).toUpperCase() + '-';
                $('#student_initials').val(initials);
            } else if (name.trim() !== '' && lastname.trim() === '') {
                var initials = name.trim().charAt(0).toUpperCase() + '-';
                $('#student_initials').val(initials);
            }
        }

        $('#student_name, #student_lastname').on('keyup blur', function() {
            updateInitials();
        });

        // Validar que el código sea solo números de 4 dígitos
        $('#student_code').on('input', function() {
            $(this).val($(this).val().replace(/[^0-9]/g, '').substring(0, 4));
        });
    });
</script>
<?= $this->endSection() ?>
