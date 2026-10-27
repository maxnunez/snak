<?php helper('text'); ?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1"><?= esc(lang('Sale.createTitle')) ?></h1>
        <p class="text-muted mb-0"><?= esc(lang('Sale.createHeading')) ?></p>
    </div>
    <a href="/admin/sales" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> <?= esc(lang('Sale.backToList')) ?>
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
    <!-- Catálogo de productos -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= esc(lang('Sale.catalog')) ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="catalogTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">Img</th>
                                <th><?= esc(lang('Product.name')) ?></th>
                                <th class="text-right"><?= esc(lang('Product.price')) ?></th>
                                <th class="text-center"><?= esc(lang('Product.stock')) ?></th>
                                <th class="text-center" style="width: 120px;"><?= esc(lang('Product.actions')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr data-product-id="<?= $product->id ?>" data-price="<?= $product->price ?>" data-stock="<?= $product->stock ?>">
                                    <td>
                                        <?php if (!empty($product->image)): ?>
                                            <img src="/uploads/products/<?= esc(basename($product->image)) ?>" 
                                                 alt="<?= esc($product->name) ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 20px; height: 20px; object-fit: cover;">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-image"></i></span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <strong><?= esc($product->name) ?></strong>
                                        <?php if (!empty($product->description)): ?>
                                            <br><small class="text-muted"><?= esc(character_limiter($product->description, 50)) ?></small>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-right font-weight-bold">Bs <?= number_format((float)$product->price, 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <?php if ((int)$product->stock === 0): ?>
                                            <span class="badge badge-danger"><?= esc(lang('Product.outOfStock')) ?></span>
                                        <?php elseif ((int)$product->stock <= (int)$product->min_stock): ?>
                                            <span class="badge badge-warning"><?= esc($product->stock) ?></span>
                                        <?php else: ?>
                                            <span class="text-success"><?= esc($product->stock) ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int)$product->stock > 0): ?>
                                            <button type="button" class="btn btn-primary btn-sm add-to-cart"
                                                data-id="<?= $product->id ?>"
                                                data-name="<?= esc($product->name) ?>"
                                                data-price="<?= $product->price ?>"
                                                data-stock="<?= $product->stock ?>">
                                                <i class="fas fa-plus mr-1"></i> <?= esc(lang('Sale.addToCart')) ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-times mr-1"></i> <?= esc(lang('Product.outOfStock')) ?>
                                            </button>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Carrito de venta -->
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-shopping-cart mr-2"></i><?= esc(lang('Sale.cart')) ?></h5>
                <span class="badge badge-primary" id="cartCount">0</span>
            </div>
            <div class="card-body">
                <form id="saleForm" method="post" action="/admin/sales/store" novalidate>
                    <?= csrf_field() ?>

                    <div id="cartItems">
                        <div class="text-center text-muted py-4" id="emptyCart">
                            <i class="fas fa-shopping-cart fa-3x mb-2"></i>
                            <p class="mb-0"><?= esc(lang('Sale.cartEmpty')) ?></p>
                            <small><?= esc(lang('Sale.selectProducts')) ?></small>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="payment_method"><?= esc(lang('Sale.paymentMethod')) ?></label>
                        <select class="form-control" id="payment_method" name="payment_method">
                            <option value="cash"><?= esc(lang('Sale.cash')) ?></option>
                            <option value="card"><?= esc(lang('Sale.card')) ?></option>
                            <option value="transfer"><?= esc(lang('Sale.transfer')) ?></option>
                            <option value="qr"><?= esc(lang('Sale.qr')) ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes"><?= esc(lang('Sale.notes')) ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="<?= esc(lang('Sale.observations')) ?>"></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <small class="text-muted"><?= esc(lang('Sale.subtotal')) ?></small>
                            <h4 class="mb-0 text-primary" id="cartSubtotal">Bs 0,00</h4>
                        </div>
                        <div>
                            <small class="text-muted"><?= esc(lang('Sale.total')) ?></small>
                            <h3 class="mb-0 text-success" id="cartTotal">Bs 0,00</h3>
                        </div>
                    </div>

                    <input type="hidden" name="items" id="cartItemsJson" value="[]">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit" disabled>
                            <i class="fas fa-cash-register mr-1"></i> <?= esc(lang('Sale.processSale')) ?>
                        </button>
                        <a href="/admin/sales" class="btn btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i> <?= esc(lang('Product.cancel')) ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // DataTable para catálogo
        if ($.fn.DataTable) {
            $('#catalogTable').DataTable({
                language: { url: '/assets/vendor/datatables/1.13.11/Spanish.json' },
                pageLength: 10,
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 4] }]
            });
        }

        // Estado del carrito
        var cart = [];

        function updateCartDisplay() {
            var $cartItems = $('#cartItems');
            var $emptyCart = $('#emptyCart');
            var $cartCount = $('#cartCount');
            var $cartSubtotal = $('#cartSubtotal');
            var $cartTotal = $('#cartTotal');
            var $btnSubmit = $('#btnSubmit');
            var $cartItemsJson = $('#cartItemsJson');

            if (cart.length === 0) {
                $emptyCart.show();
                $cartItems.find('.cart-item').remove();
                $btnSubmit.prop('disabled', true);
            } else {
                $emptyCart.hide();
                $btnSubmit.prop('disabled', false);
            }

            $cartCount.text(cart.length);

            var subtotal = 0;
            cart.forEach(function(item) {
                subtotal += item.subtotal;
            });

            $cartSubtotal.text('Bs ' + subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $cartTotal.text('Bs ' + subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            $cartItemsJson.val(JSON.stringify(cart));
        }

        function renderCartItems() {
            var $cartItems = $('#cartItems');
            $cartItems.find('.cart-item').remove();

            cart.forEach(function(item, index) {
                var html = '<div class="cart-item d-flex justify-content-between align-items-center py-2 border-bottom" data-index="${index}">';
                html += '<div class="flex-grow-1">';
                html += '<strong>' + item.name + '</strong><br>';
                html += '<small class="text-muted">Bs ' + item.price.toLocaleString('es-AR', {minimumFractionDigits: 2}) + ' x ' + item.quantity + '</small>';
                html += '</div>';
                html += '<div class="d-flex align-items-center">';
                html += '<span class="font-weight-bold mr-3">Bs ' + item.subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2}) + '</span>';
                html += '<div class="input-group input-group-sm" style="width: 100px;">';
                html += '<div class="input-group-prepend">';
                html += '<button type="button" class="btn btn-outline-secondary qty-decrease" data-index="' + index + '">-</button>';
                html += '</div>';
                html += '<input type="number" class="form-control text-center qty-input" value="' + item.quantity + '" min="1" max="' + item.stock + '" data-index="' + index + '" readonly>';
                html += '<div class="input-group-append">';
                html += '<button type="button" class="btn btn-outline-secondary qty-increase" data-index="' + index + '">+</button>';
                html += '</div></div>';
                html += '<button type="button" class="btn btn-outline-danger btn-sm ml-2 remove-item" data-index="' + index + '" title="Eliminar"><i class="fas fa-trash"></i></button>';
                html += '</div></div>';
                $cartItems.append(html);
            });
            updateCartDisplay();
        }

        $(document).on('click', '.add-to-cart', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            var name = $btn.data('name');
            var price = parseFloat($btn.data('price'));
            var stock = parseInt($btn.data('stock'));

            var existing = cart.findIndex(function(item) { return item.id === id; });

            if (existing >= 0) {
                if (cart[existing].quantity < stock) {
                    cart[existing].quantity++;
                    cart[existing].subtotal = cart[existing].quantity * cart[existing].price;
                } else {
                    Swal.fire({ icon: 'warning', title: '<?= esc(lang('Sale.stockMax')) ?>', text: '<?= esc(lang('Sale.stockMaxText')) ?>', confirmButtonColor: '#003366' });
                    return;
                }
            } else {
                cart.push({ id: id, name: name, price: price, quantity: 1, stock: stock, subtotal: price });
            }

            renderCartItems();
            $btn.prop('disabled', existing >= 0 ? cart[existing].quantity >= stock : false);
        });

        $(document).on('click', '.qty-increase', function() {
            var index = $(this).data('index');
            if (cart[index].quantity < cart[index].stock) {
                cart[index].quantity++;
                cart[index].subtotal = cart[index].quantity * cart[index].price;
                renderCartItems();
            }
        });

        $(document).on('click', '.qty-decrease', function() {
            var index = $(this).data('index');
            if (cart[index].quantity > 1) {
                cart[index].quantity--;
                cart[index].subtotal = cart[index].quantity * cart[index].price;
                renderCartItems();
            }
        });

        $(document).on('click', '.remove-item', function() {
            var index = $(this).data('index');
            var productId = cart[index].id;
            cart.splice(index, 1);
            renderCartItems();
            $('.add-to-cart[data-id="' + productId + '"]').prop('disabled', false);
        });

        $('#saleForm').on('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: '<?= esc(lang('Sale.cartEmptyAlert')) ?>', text: '<?= esc(lang('Sale.cartEmptyText')) ?>', confirmButtonColor: '#003366' });
            }
        });
    });
</script>
<?= $this->endSection() ?>
