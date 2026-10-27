<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'category_id', 'name', 'slug', 'description',
        'price', 'cost', 'image', 'stock', 'min_stock',
        'is_active', 'is_featured',
    ];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $validationRules  = [
        'name'        => 'required|min_length[2]|max_length[150]|is_unique[products.name,id,{id}]',
        'slug'        => 'required|min_length[2]|max_length[180]|alpha_dash|is_unique[products.slug,id,{id}]',
        'category_id' => 'permit_empty|integer|greater_than[0]',
        'price'       => 'required|decimal|greater_than[0]',
        'cost'        => 'permit_empty|decimal|greater_than_equal_to[0]',
        'stock'       => 'integer|greater_than_equal_to[0]',
        'min_stock'   => 'integer|greater_than_equal_to[0]',
        'is_active'   => 'in_list[0,1]',
        'is_featured' => 'in_list[0,1]',
    ];
    protected $validationMessages = [
        'name' => [
            'required'   => 'Product.nameRequired',
            'min_length' => 'Product.nameLength',
            'max_length' => 'Product.nameLength',
            'is_unique'  => 'Product.nameDuplicate',
        ],
        'slug' => [
            'required'   => 'Product.slugRequired',
            'alpha_dash' => 'Product.slugInvalid',
            'is_unique'  => 'Product.slugDuplicate',
        ],
        'price' => [
            'required'  => 'Product.priceRequired',
            'decimal'   => 'Product.priceInvalid',
            'greater_than' => 'Product.pricePositive',
        ],
    ];
    protected $skipValidation   = false;

    public function getActive(int $limit = 0, int $offset = 0): array
    {
        $builder = $this
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.is_active', 1)
            ->orderBy('products.name', 'ASC');
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }
        return $builder->findAll();
    }

    public function getFeatured(int $limit = 8): array
    {
        return $this
            ->where('is_active', 1)
            ->where('is_featured', 1)
            ->where('stock >', 0)
            ->orderBy('name', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function getLowStock(int $limit = 50): array
    {
        return $this
            ->where('is_active', 1)
            ->where('stock <= min_stock')
            ->orderBy('stock', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function getByCategory(int $categoryId, int $limit = 0): array
    {
        $builder = $this->where('category_id', $categoryId)->where('is_active', 1)->orderBy('name', 'ASC');
        if ($limit > 0) {
            $builder->limit($limit);
        }
        return $builder->findAll();
    }

    public function adjustStock(int $productId, int $quantity): bool
    {
        $product = $this->find($productId);
        if ($product === null) {
            return false;
        }

        $newStock = (int) $product->stock + $quantity;
        if ($newStock < 0) {
            return false;
        }

        return $this->update($productId, ['stock' => $newStock]) !== false;
    }

    public function decreaseStock(int $productId, int $quantity): bool
    {
        return $this->adjustStock($productId, -$quantity);
    }

    public function increaseStock(int $productId, int $quantity): bool
    {
        return $this->adjustStock($productId, $quantity);
    }
}