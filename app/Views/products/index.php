<?php helper('text'); ?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Product.listTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Product.listHeading')) ?></p>
    </div>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> <?= esc(lang('Product.createNew')) ?>
    </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="<?= esc(lang('Product.searchPlaceholder')) ?>" value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="category" class="form-control">
                    <option value="0"><?= esc(lang('Product.allCategories')) ?></option>
                    <?php foreach ($categories as $id => $name) { ?>
                        <option value="<?= $id ?>" <?= ($filters['category'] ?? 0) == $id ? 'selected' : '' ?>>
                            <?= esc($name) ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="stock" class="form-control">
                    <option value=""><?= esc(lang('Product.allStock')) ?></option>
                    <option value="low" <?= ($filters['stock'] ?? '') === 'low' ? 'selected' : '' ?>><?= esc(lang('Product.lowStock')) ?></option>
                    <option value="out" <?= ($filters['stock'] ?? '') === 'out' ? 'selected' : '' ?>><?= esc(lang('Product.outOfStock')) ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter mr-1"></i> <?= esc(lang('Product.filter')) ?>
                </button>
            </div>
            <div class="col-md-2">
                <a href="/admin/products" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times mr-1"></i> <?= esc(lang('Product.clearFilters')) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Alertas -->
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

<!-- Tabla de productos -->
<div class="card">
    <div class="card-body p-5">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">Img</th>
                        <th><?= esc(lang('Product.name')) ?></th>
                        <th><?= esc(lang('Product.category')) ?></th>
                        <th class="text-right"><?= esc(lang('Product.price')) ?></th>
                        <th class="text-center"><?= esc(lang('Product.stock')) ?></th>
                        <th class="text-center"><?= esc(lang('Product.featured')) ?></th>
                        <th class="text-center"><?= esc(lang('Product.status')) ?></th>
                        <th class="text-center" style="width: 120px;"><?= esc(lang('Product.actions')) ?></th>
                        <th class="text-center" style="width: 100px;"><?= esc(lang('Product.quickOrder')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)) {
                        echo '<tr data-tableempty="true"><td colspan="9">No hay productos registrados.</td></tr>';
                    } else { ?>
                        <?php foreach ($products as $product) { ?>
                            <tr>
                                <td>
                                    <?php if (! empty($product->image)) { ?>
                                        <img src="/uploads/products/<?= esc(basename($product->image)) ?>"
                                             alt="<?= esc($product->name) ?>"
                                             class="img-thumbnail"
                                             style="width: 24px; height: 24px; object-fit: cover;">
                                    <?php } else { ?>
                                        <span class="text-muted"><i class="fas fa-image"></i></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <strong><?= esc($product->name) ?></strong>
                                    <?php if (! empty($product->description)) { ?>
                                        <br><small class="text-muted"><?= esc(character_limiter($product->description, 60)) ?></small>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (! empty($product->category_name)) { ?>
                                        <span class="badge badge-info"><?= esc($product->category_name) ?></span>
                                    <?php } else { ?>
                                        <span class="text-muted"><?= esc(lang('Product.noCategory')) ?></span>
                                    <?php } ?>
                                </td>
                                <td class="text-right font-weight-bold">Bs <?= number_format((float) $product->price, 2, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php if ((int) $product->stock === 0) { ?>
                                        <span class="badge badge-danger"><?= esc(lang('Product.outOfStock')) ?></span>
                                    <?php } elseif ((int) $product->stock <= (int) $product->min_stock) { ?>
                                        <span class="badge badge-warning"><?= esc($product->stock) ?> (<?= esc(lang('Product.low')) ?>)</span>
                                    <?php } else { ?>
                                        <span class="text-success"><?= esc($product->stock) ?></span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int) $product->is_featured === 1) { ?>
                                        <span class="badge badge-warning"><i class="fas fa-star mr-1"></i><?= esc(lang('Product.yes')) ?></span>
                                    <?php } else { ?>
                                        <span class="text-muted"><?= esc(lang('Product.no')) ?></span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int) $product->is_active === 1) { ?>
                                        <span class="badge badge-success"><?= esc(lang('Product.active')) ?></span>
                                    <?php } else { ?>
                                        <span class="badge badge-secondary"><?= esc(lang('Product.inactive')) ?></span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/admin/products/edit/<?= $product->id ?>" class="btn btn-outline-primary" title="<?= esc(lang('Product.edit')) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin/products/toggle-featured/<?= $product->id ?>" class="btn btn-outline-warning" title="<?= (int) $product->is_featured ? lang('Product.removeFeatured') : lang('Product.addFeatured') ?>">
                                            <i class="fas fa-star"></i>
                                        </a>
                                        <?php if ((int) $product->is_active === 1) { ?>
                                            <form method="post" action="/admin/products/delete/<?= $product->id ?>" class="d-inline" onsubmit="return confirm('<?= esc(lang('Product.confirmDelete')) ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="<?= esc(lang('Product.delete')) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <form method="post" action="/admin/products/delete/<?= $product->id ?>" class="d-inline" onsubmit="return confirm('<?= esc(lang('Product.confirmActivate')) ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-success" title="<?= esc(lang('Product.activate')) ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ((int) $product->stock > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary order-btn" data-product-id="<?= $product->id ?>" data-product-name="<?= esc($product->name, 'attr') ?>" title="<?= esc(lang('Product.orderProduct')) ?>">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fas fa-times"></i></span>
                                    <?php endif ?>
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

<!-- Modal: Pedido rápido -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="orderModalLabel"><i class="fas fa-shopping-cart mr-2"></i><?= esc(lang('Product.quickOrder')) ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="/admin/orders/store">
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
                    <div class="form-group">
                        <label><?= esc(lang('Order.studentCode')) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="student_code" id="modal_student_code" maxlength="4" required placeholder="0000" inputmode="numeric">
                        <small class="form-text text-muted"><?= esc(lang('Order.studentCodeHelp')) ?></small>
                    </div>
                    <div class="form-group">
                        <label><?= esc(lang('Order.studentInitials')) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="student_initials" id="modal_student_initials" maxlength="10" required placeholder="JD-">
                        <small class="form-text text-muted"><?= esc(lang('Order.studentInitialsHelp')) ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= esc(lang('Product.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i><?= esc(lang('Order.registerOrder')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // DataTable para productos
        if ($.fn.DataTable) {
            $('#productsTable').DataTable({
                language: {
                    url: '/assets/vendor/datatables/1.13.11/Spanish.json'
                },
                pageLength: 15,
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 7, 8] }
                ]
            });
        }

        // Abrir modal de pedido rápido
        $('.order-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            $('#modal_product_id').val(productId);
            $('#modal_product_name').text(productName);
            $('#modal_student_name').val('').focus();
            $('#modal_student_lastname').val('');
            $('#modal_student_code').val('');
            $('#modal_student_initials').val('');
            $('#orderModal').modal('show');
        });

        // Auto-generar iniciales
        function updateInitials() {
            var name = $('#modal_student_name').val() || '';
            var lastname = $('#modal_student_lastname').val() || '';
            if (name.trim() !== '' && lastname.trim() !== '') {
                $('#modal_student_initials').val(name.trim().charAt(0).toUpperCase() + lastname.trim().charAt(0).toUpperCase() + '-');
            } else if (name.trim() !== '') {
                $('#modal_student_initials').val(name.trim().charAt(0).toUpperCase() + '-');
            }
        }
        $('#modal_student_name, #modal_student_lastname').on('keyup blur', updateInitials);

        // Código solo números, máx 4 dígitos
        $('#modal_student_code').on('input', function() {
            $(this).val($(this).val().replace(/[^0-9]/g, '').substring(0, 4));
        });
    });
</script>
<?= $this->endSection() ?>
