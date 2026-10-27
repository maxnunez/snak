<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProductController extends BaseController
{
    public function publicIndex(): ResponseInterface|string
    {
        $model = model(ProductModel::class);
        $products = $model->where('is_active', 1)
            ->where('stock >', 0)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('products/public', [
            'products' => $products,
        ]);
    }

    public function index(): ResponseInterface|string
    {
        $page     = (int) ($this->request->getGet('page') ?? 1);
        $perPage  = 15;
        $search   = trim((string) ($this->request->getGet('search') ?? ''));
        $category = (int) ($this->request->getGet('category') ?? 0);
        $stock    = (string) ($this->request->getGet('stock') ?? '');

        $model = model(ProductModel::class);
        $builder = $model
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left');

        if ($search !== '') {
            $builder->groupStart()
                ->like('products.name', $search)
                ->orLike('products.description', $search)
                ->orLike('products.slug', $search)
                ->groupEnd();
        }

        if ($category > 0) {
            $builder->where('products.category_id', $category);
        }

        if ($stock === 'low') {
            $builder->where('products.stock <= products.min_stock');
        } elseif ($stock === 'out') {
            $builder->where('products.stock', 0);
        }

        $builder->orderBy('products.name', 'ASC');

        $products = $model->paginate($perPage, 'default', $page);
        $pager    = $model->pager;

        $categories = model(CategoryModel::class)->getDropdownOptions();

        return view('products/index', [
            'products'   => $products,
            'pager'      => $pager,
            'categories' => $categories,
            'filters'    => [
                'search'   => $search,
                'category' => $category,
                'stock'    => $stock,
            ],
        ]);
    }

    public function create(): ResponseInterface|string
    {
        $categories = model(CategoryModel::class)->getDropdownOptions();

        return view('products/form', [
            'categories' => $categories,
            'product'    => null,
            'errors'     => session('errors') ?? [],
        ]);
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'name'        => 'required|min_length[2]|max_length[150]|is_unique[products.name]',
            'slug'        => 'required|min_length[2]|max_length[180]|alpha_dash|is_unique[products.slug]',
            'category_id' => 'permit_empty|integer|greater_than[0]',
            'description' => 'permit_empty|max_length[1000]',
            'price'       => 'required|decimal|greater_than[0]',
            'cost'        => 'permit_empty|decimal|greater_than_equal_to[0]',
            'stock'       => 'integer|greater_than_equal_to[0]',
            'min_stock'   => 'integer|greater_than_equal_to[0]',
            'is_active'   => 'in_list[0,1]',
            'is_featured' => 'in_list[0,1]',
            'image'       => 'permit_empty|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost([
            'name', 'slug', 'category_id', 'description',
            'price', 'cost', 'stock', 'min_stock',
            'is_active', 'is_featured',
        ]);

        // Handle image uploaded via AJAX (uploaded_image_name hidden field)
        $uploadedImageName = $this->request->getPost('uploaded_image_name');
        if (!empty($uploadedImageName)) {
            $data['image'] = $uploadedImageName;
        }

        // Handle direct image upload (fallback)
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && ! $image->hasMoved()) {
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads/products', $newName);
            $data['image'] = $newName;
        }

        // Normalize category_id
        if (empty($data['category_id'])) {
            $data['category_id'] = null;
        }

        // Generate slug from name if empty
        if (empty($data['slug'])) {
            $data['slug'] = url_title($data['name'], '-', true);
        }

        model(ProductModel::class)->insert($data);

        return redirect()->to('/admin/products')
            ->with('success', lang('Product.createdSuccess'));
    }

    public function edit(int $id): ResponseInterface|string
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Product.notFound'));
        }

        $categories = model(CategoryModel::class)->getDropdownOptions();

        return view('products/form', [
            'categories' => $categories,
            'product'    => $product,
            'errors'     => session('errors') ?? [],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Product.notFound'));
        }

        $rules = [
            'name'        => "required|min_length[2]|max_length[150]|is_unique[products.name,id,{$id}]",
            'slug'        => "required|min_length[2]|max_length[180]|alpha_dash|is_unique[products.slug,id,{$id}]",
            'category_id' => 'permit_empty|integer|greater_than[0]',
            'description' => 'permit_empty|max_length[1000]',
            'price'       => 'required|decimal|greater_than[0]',
            'cost'        => 'permit_empty|decimal|greater_than_equal_to[0]',
            'stock'       => 'integer|greater_than_equal_to[0]',
            'min_stock'   => 'integer|greater_than_equal_to[0]',
            'is_active'   => 'in_list[0,1]',
            'is_featured' => 'in_list[0,1]',
            'image'       => 'permit_empty|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost([
            'name', 'slug', 'category_id', 'description',
            'price', 'cost', 'stock', 'min_stock',
            'is_active', 'is_featured',
        ]);

        // Handle image uploaded via AJAX (uploaded_image_name hidden field)
        $uploadedImageName = $this->request->getPost('uploaded_image_name');
        if (!empty($uploadedImageName)) {
            // Remove old image if exists
            if (!empty($product->image) && file_exists(FCPATH . 'uploads/products/' . basename($product->image))) {
                unlink(FCPATH . 'uploads/products/' . basename($product->image));
            }
            $data['image'] = $uploadedImageName;
        }

        // Handle image removal
        if ($this->request->getPost('remove_image')) {
            if (!empty($product->image) && file_exists(FCPATH . 'uploads/products/' . basename($product->image))) {
                unlink(FCPATH . 'uploads/products/' . basename($product->image));
            }
            $data['image'] = null;
        }

        // Handle direct image upload (fallback)
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && ! $image->hasMoved()) {
            // Remove old image if exists
            if (!empty($product->image) && file_exists(FCPATH . 'uploads/products/' . basename($product->image))) {
                unlink(FCPATH . 'uploads/products/' . basename($product->image));
            }
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads/products', $newName);
            $data['image'] = $newName;
        }

        if (empty($data['category_id'])) {
            $data['category_id'] = null;
        }

        if (empty($data['slug'])) {
            $data['slug'] = url_title($data['name'], '-', true);
        }

        model(ProductModel::class)->update($id, $data);

        return redirect()->to('/admin/products')
            ->with('success', lang('Product.updatedSuccess'));
    }

    public function delete(int $id): ResponseInterface
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Product.notFound'));
        }

        // Check if product has sales
        $hasSales = model(\App\Models\SaleItemModel::class)
            ->where('product_id', $id)
            ->countAllResults() > 0;

        if ($hasSales) {
            // Soft delete - just deactivate
            model(ProductModel::class)->update($id, ['is_active' => 0]);
            $message = lang('Product.deactivatedSuccess');
        } else {
            model(ProductModel::class)->delete($id);
            $message = lang('Product.deletedSuccess');
        }

        return redirect()->to('/admin/products')
            ->with('success', $message);
    }

    public function toggleFeatured(int $id): ResponseInterface
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Product.notFound'));
        }

        $newValue = (int) $product->is_featured === 1 ? 0 : 1;
        model(ProductModel::class)->update($id, ['is_featured' => $newValue]);

        return redirect()->back()
            ->with('success', $newValue ? lang('Product.featuredOn') : lang('Product.featuredOff'));
    }

    public function adjustStock(int $id): ResponseInterface
    {
        $product = model(ProductModel::class)->find($id);

        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Product.notFound'));
        }

        $adjustment = (int) $this->request->getPost('adjustment');
        $reason     = trim((string) $this->request->getPost('reason'));

        if ($adjustment === 0) {
            return redirect()->back()
                ->with('errors', ['adjustment' => lang('Product.adjustmentRequired')]);
        }

        if ($product->stock + $adjustment < 0) {
            return redirect()->back()
                ->with('errors', ['adjustment' => lang('Product.insufficientStock')]);
        }

        model(ProductModel::class)->adjustStock($id, $adjustment);

        // Log the adjustment (could be extended to a stock_log table)
        log_message('info', "Stock adjustment: Product {$product->name} (ID: {$id}) by {$adjustment}. Reason: {$reason}");

        return redirect()->back()
            ->with('success', lang('Product.stockAdjusted'));
    }

    public function uploadImage(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $image = $this->request->getFile('image');

        if (!$image || !$image->isValid() || $image->hasMoved()) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Product.imageUploadError')]);
        }

        // Validate the file is an image
        if (!$image->isImage()) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Product.imageInvalid')]);
        }

        // Check size (max 2MB)
        if ($image->getSize() > 2048 * 1024) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Product.imageErrorSize')]);
        }

        // Check MIME type
        $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($image->getMimeType(), $allowedMimes, true)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Product.imageErrorMime')]);
        }

        // Generate unique filename
        $newName = $image->getRandomName();

        // Ensure upload directory exists
        $uploadDir = FCPATH . 'uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move the file
        if (!$image->move($uploadDir, $newName)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Product.imageUploadError')]);
        }

        // Return the image URL
        $imageUrl = base_url('uploads/products/' . $newName);

        return $this->response->setJSON([
            'success' => true,
            'image' => $newName,
            'url' => $imageUrl,
            'message' => lang('Product.imageUploaded')
        ]);
    }
}