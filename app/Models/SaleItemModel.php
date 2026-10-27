<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SaleItemModel extends Model
{
    protected $table            = 'sale_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'sale_id', 'product_id', 'quantity', 'unit_price', 'subtotal',
    ];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $validationRules  = [
        'sale_id'    => 'required|integer|greater_than[0]',
        'product_id' => 'required|integer|greater_than[0]',
        'quantity'   => 'required|integer|greater_than[0]',
        'unit_price' => 'required|decimal|greater_than[0]',
        'subtotal'   => 'required|decimal|greater_than_equal_to[0]',
    ];
    protected $validationMessages = [
        'quantity' => [
            'required' => 'SaleItem.qtyRequired',
            'integer'  => 'SaleItem.qtyInvalid',
        ],
    ];
    protected $skipValidation   = false;

    public function getBySale(int $saleId): array
    {
        return $this
            ->select('sale_items.*, products.name as product_name, products.slug as product_slug')
            ->join('products', 'products.id = sale_items.product_id', 'left')
            ->where('sale_id', $saleId)
            ->findAll();
    }
}