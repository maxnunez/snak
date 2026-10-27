<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\HTTP\ResponseInterface;

class CategoryController extends BaseController
{
    public function index(): ResponseInterface|string
    {
        $model = model(CategoryModel::class);
        $categories = $model->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();

        return view('categories/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): ResponseInterface|string
    {
        return view('categories/form', [
            'category' => null,
            'errors'   => session('errors') ?? [],
        ]);
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'name'        => 'required|min_length[2]|max_length[100]|is_unique[categories.name]',
            'slug'        => 'required|min_length[2]|max_length[120]|alpha_dash|is_unique[categories.slug]',
            'description' => 'permit_empty|max_length[500]',
            'sort_order'  => 'integer|greater_than_equal_to[0]',
            'is_active'   => 'in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost([
            'name', 'slug', 'description', 'sort_order', 'is_active',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = url_title($data['name'], '-', true);
        }

        model(CategoryModel::class)->insert($data);

        return redirect()->to('/admin/categories')
            ->with('success', lang('Category.createdSuccess'));
    }

    public function edit(int $id): ResponseInterface|string
    {
        $category = model(CategoryModel::class)->find($id);

        if ($category === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Category.notFound'));
        }

        return view('categories/form', [
            'category' => $category,
            'errors'   => session('errors') ?? [],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $category = model(CategoryModel::class)->find($id);

        if ($category === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Category.notFound'));
        }

        $rules = [
            'name'        => "required|min_length[2]|max_length[100]|is_unique[categories.name,id,{$id}]",
            'slug'        => "required|min_length[2]|max_length[120]|alpha_dash|is_unique[categories.slug,id,{$id}]",
            'description' => 'permit_empty|max_length[500]',
            'sort_order'  => 'integer|greater_than_equal_to[0]',
            'is_active'   => 'in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost([
            'name', 'slug', 'description', 'sort_order', 'is_active',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = url_title($data['name'], '-', true);
        }

        model(CategoryModel::class)->update($id, $data);

        return redirect()->to('/admin/categories')
            ->with('success', lang('Category.updatedSuccess'));
    }

    public function delete(int $id): ResponseInterface
    {
        $category = model(CategoryModel::class)->find($id);

        if ($category === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Category.notFound'));
        }

        // Check if category has products
        $hasProducts = model(\App\Models\ProductModel::class)
            ->where('category_id', $id)
            ->countAllResults() > 0;

        if ($hasProducts) {
            // Soft delete - just deactivate
            model(CategoryModel::class)->update($id, ['is_active' => 0]);
            $message = lang('Category.deactivatedSuccess');
        } else {
            model(CategoryModel::class)->delete($id);
            $message = lang('Category.deletedSuccess');
        }

        return redirect()->to('/admin/categories')
            ->with('success', $message);
    }
}