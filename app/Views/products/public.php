<?php helper('text'); ?>

<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-primary mb-1"><i class="fas fa-utensils mr-2"></i><?= esc(lang('Product.listTitle')) ?></h1>
            <p class="text-muted mb-0"><?= esc(lang('Product.publicSubtitle')) ?></p>
        </div>
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

    <?php if (empty($products)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4><?= esc(lang('Product.noProductsAvailable')) ?></h4>
                <p class="text-muted"><?= esc(lang('Product.noProductsPublic')) ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($product->image)): ?>
                            <img src="/uploads/products/<?= esc(basename($product->image)) ?>" 
                                 alt="<?= esc($product->name) ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <span class="text-muted"><i class="fas fa-image fa-3x"></i></span>
                            </div>
                        <?php endif ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= esc($product->name) ?></h5>
                            <?php if (!empty($product->description)): ?>
                                <p class="card-text text-muted small"><?= esc(character_limiter($product->description, 80)) ?></p>
                            <?php endif ?>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold text-primary">
                                        Bs <?= number_format((float)$product->price, 2, ',', '.') ?>
                                    </span>
                                    <span class="badge badge-success">
                                        <?= esc(lang('Product.stock')) ?>: <?= (int)$product->stock ?>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-primary btn-block order-btn" 
                                        data-product-id="<?= $product->id ?>" 
                                        data-product-name="<?= esc($product->name, 'attr') ?>">
                                    <i class="fas fa-shopping-cart mr-2"></i><?= esc(lang('Product.orderProduct')) ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<!-- Modal: Pedido rápido -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="orderModalLabel"><i class="fas fa-shopping-cart mr-2"></i><?= esc(lang('Product.quickOrder')) ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="/pedidos/store">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="modal_product_id">
                    <div class="form-group">
                        <label class="font-weight-bold"><span id="modal_product_name"></span></label>
                        <small class="text-muted d-block mb-2">Snack seleccionado</small>
                    </div>
                    <div class="form-group">
                        <label><?= esc(lang('Order.studentName')) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="student_name" id="modal_student_name" maxlength="100" required autofocus>
                    </div>
                    <div class="form-group">
                        <label><?= esc(lang('Order.studentLastname')) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="student_lastname" id="modal_student_lastname" maxlength="100" required>
                    </div>
                    <input type="hidden" name="student_code" id="modal_student_code" value="">
                    <input type="hidden" name="student_initials" id="modal_student_initials" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= esc(lang('Product.cancel')) ?></button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i><?= esc(lang('Order.registerOrder')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // Abrir modal de pedido rápido
        $('.order-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            $('#modal_product_id').val(productId);
            $('#modal_product_name').text(productName);
            $('#modal_student_name').val('').focus();
            $('#modal_student_lastname').val('');
            $('#orderModal').modal('show');
        });
    });
</script>
<?= $this->endSection() ?>
