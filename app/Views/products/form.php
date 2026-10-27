<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 text-primary mb-1">
            <?= $product ? esc(lang('Product.editTitle')) : esc(lang('Product.createTitle')) ?>
        </h1>
        <p class="text-muted mb-0">
            <?= $product ? esc(lang('Product.editHeading')) : esc(lang('Product.createHeading')) ?>
        </p>
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

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $product ? '/admin/products/update/' . $product->id : '/admin/products/store' ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name"><?= esc(lang('Product.name')) ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                            id="name"
                            name="name"
                            value="<?= esc(old('name', $product->name ?? '')) ?>"
                            maxlength="150"
                            required
                            autofocus
                        >
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['name']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="slug"><?= esc(lang('Product.slug')) ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">/</span>
                            </div>
                            <input
                                type="text"
                                class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>"
                                id="slug"
                                name="slug"
                                value="<?= esc(old('slug', $product->slug ?? '')) ?>"
                                maxlength="180"
                                required
                            >
                        </div>
                        <?php if (isset($errors['slug'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['slug']) ?></div>
                        <?php endif ?>
                        <small class="form-text text-muted"><?= esc(lang('Product.slugHelp')) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="description"><?= esc(lang('Product.description')) ?></label>
                        <textarea
                            class="form-control"
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="1000"
                        ><?= esc(old('description', $product->description ?? '')) ?></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="form-group">
                        <label for="image"><?= esc(lang('Product.image')) ?></label>
                        <div id="imagePreviewContainer" class="mb-3">
                            <?php if (!empty($product->image)): ?>
                                <img src="/uploads/products/<?= esc(basename($product->image)) ?>" 
                                     alt="<?= esc($product->name) ?>" 
                                     class="img-thumbnail" 
                                     style="max-height: 200px;"
                                     id="currentImagePreview">
                                <div class="mt-2">
                                    <input type="checkbox" id="remove_image" name="remove_image" value="1">
                                    <label for="remove_image"><?= esc(lang('Product.removeImage')) ?></label>
                                </div>
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px; border: 2px dashed #dee2e6;" id="imageDropZone">
                                    <span class="text-muted"><i class="fas fa-cloud-upload-alt fa-2x"></i><br><small>Haz clic para subir imagen</small></span>
                                </div>
                            <?php endif ?>
                        </div>
                        <input type="file" class="form-control-file" id="image" name="image" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnSelectImage" style="<?= empty($product->image) ? '' : 'display: none;' ?>">
                            <i class="fas fa-upload mr-1"></i>Seleccionar imagen
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnUploadImage" style="display: none;" data-product-id="<?= $product->id ?? 0 ?>">
                            <i class="fas fa-cloud-upload-alt mr-1"></i>Subir imagen
                        </button>
                        <small class="form-text text-muted"><?= esc(lang('Product.imageHelp')) ?></small>
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback d-block"><?= esc($errors['image']) ?></div>
                        <?php endif ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="category_id"><?= esc(lang('Product.category')) ?></label>
                        <select class="form-control" id="category_id" name="category_id">
                            <option value=""><?= esc(lang('Product.selectCategory')) ?></option>
                            <?php foreach ($categories as $id => $name): ?>
                                <option value="<?= $id ?>" <?= (old('category_id', $product->category_id ?? '') == $id) ? 'selected' : '' ?>>
                                    <?= esc($name) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price"><?= esc(lang('Product.price')) ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bs</span>
                            </div>
                            <input
                                type="number"
                                class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>"
                                id="price"
                                name="price"
                                value="<?= esc(old('price', $product->price ?? '')) ?>"
                                step="0.01"
                                min="0.01"
                                required
                            >
                        </div>
                        <?php if (isset($errors['price'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['price']) ?></div>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="cost"><?= esc(lang('Product.cost')) ?></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bs</span>
                            </div>
                            <input
                                type="number"
                                class="form-control"
                                id="cost"
                                name="cost"
                                value="<?= esc(old('cost', $product->cost ?? '')) ?>"
                                step="0.01"
                                min="0"
                            >
                        </div>
                        <small class="form-text text-muted"><?= esc(lang('Product.costHelp')) ?></small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="stock"><?= esc(lang('Product.stock')) ?></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="stock"
                                    name="stock"
                                    value="<?= esc(old('stock', $product->stock ?? 0)) ?>"
                                    min="0"
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="min_stock"><?= esc(lang('Product.minStock')) ?></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="min_stock"
                                    name="min_stock"
                                    value="<?= esc(old('min_stock', $product->min_stock ?? 5)) ?>"
                                    min="0"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="is_active"
                                        name="is_active"
                                        value="1"
                                        <?= (old('is_active', $product->is_active ?? 1) == 1) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="is_active">
                                        <?= esc(lang('Product.active')) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="is_featured"
                                        name="is_featured"
                                        value="1"
                                        <?= (old('is_featured', $product->is_featured ?? 0) == 1) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="is_featured">
                                        <?= esc(lang('Product.featured')) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end">
                <a href="/admin/products" class="btn btn-outline-secondary mr-2">
                    <?= esc(lang('Product.cancel')) ?>
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    <?= $product ? esc(lang('Product.update')) : esc(lang('Product.create')) ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        // Auto-generate slug from name
        $('#name').on('blur', function() {
            if (!$('#slug').val() || $('#slug').data('auto')) {
                var slug = $(this).val()
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $('#slug').val(slug).data('auto', true);
            }
        });

        $('#slug').on('input', function() {
            $(this).data('auto', false);
        });

        // Image upload via AJAX
        var selectedImageFile = null;

        $('#btnSelectImage').on('click', function() {
            $('#image').click();
        });

        $('#image').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.match('image.*')) {
                alert('<?= lang('Product.imageInvalid') ?>');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('<?= lang('Product.imageErrorSize') ?>');
                return;
            }

            selectedImageFile = file;

            // Show preview
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imageDropZone').html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 200px;">');
                $('#btnUploadImage').show();
                $('#btnSelectImage').hide();
            };
            reader.readAsDataURL(file);
        });

        $('#btnUploadImage').on('click', function() {
            if (!selectedImageFile) return;

            var formData = new FormData();
            formData.append('image', selectedImageFile);

            $.ajax({
                url: '/admin/products/upload-image',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response) {
                    if (response.success) {
                        $('#imageDropZone').html('<img src="' + response.url + '" class="img-thumbnail" style="max-height: 200px;" id="currentImagePreview">');
                        $('#btnUploadImage').hide();
                        $('#image').val('');
                        selectedImageFile = null;

                        // Store the uploaded image name in a hidden field for form submission
                        if ($('#uploadedImageName').length === 0) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'uploadedImageName',
                                name: 'uploaded_image_name'
                            }).appendTo('form');
                        }
                        $('#uploadedImageName').val(response.image);

                        alert('<?= lang('Product.imageUploaded') ?>');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('<?= lang('Product.imageUploadError') ?>');
                }
            });
        });

        // Remove image checkbox
        $('#remove_image').on('change', function() {
            if ($(this).is(':checked')) {
                $('#currentImagePreview').hide();
            } else {
                $('#currentImagePreview').show();
            }
        });
    });
</script>
<?= $this->endSection() ?>
