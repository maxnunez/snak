<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table            = 'sales';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id', 'total_amount', 'payment_method', 'status', 'notes',
    ];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $validationRules  = [
        'user_id'      => 'required|integer|greater_than[0]',
        'total_amount' => 'required|decimal|greater_than[0]',
        'payment_method' => 'required|in_list[cash,card,transfer,qr]',
        'status'       => 'required|in_list[pending,completed,cancelled,refunded]',
    ];
    protected $validationMessages = [
        'user_id' => [
            'required' => 'Sale.userRequired',
            'integer'  => 'Sale.userInvalid',
        ],
        'total_amount' => [
            'required' => 'Sale.amountRequired',
            'decimal'  => 'Sale.amountInvalid',
        ],
    ];
    protected $skipValidation   = false;

    public function getWithItems(int $saleId): ?object
    {
        $sale = $this->find($saleId);
        if ($sale === null) {
            return null;
        }

        $items = model(SaleItemModel::class)
            ->select('sale_items.*, products.name as product_name, products.slug as product_slug')
            ->join('products', 'products.id = sale_items.product_id', 'left')
            ->where('sale_id', $saleId)
            ->findAll();

        $sale->items = $items;
        return $sale;
    }

    public function getDailyReport(string $date): array
    {
        $sales = $this
            ->select('COUNT(*) as total_sales, SUM(total_amount) as total_revenue, payment_method')
            ->where('DATE(created_at)', $date)
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->findAll();

        $summary = [
            'total_sales'   => 0,
            'total_revenue' => 0.0,
            'by_method'     => [],
        ];

        foreach ($sales as $s) {
            $summary['total_sales']   += (int) $s->total_sales;
            $summary['total_revenue'] += (float) $s->total_revenue;
            $summary['by_method'][$s->payment_method] = [
                'count'   => (int) $s->total_sales,
                'revenue' => (float) $s->total_revenue,
            ];
        }

        return $summary;
    }

    public function getTopProducts(string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->db->table('sale_items')
            ->select('products.name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_revenue')
            ->join('products', 'products.id = sale_items.product_id')
            ->join('sales', 'sales.id = sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->where('DATE(sales.created_at) >=', $startDate)
            ->where('DATE(sales.created_at) <=', $endDate)
            ->groupBy('sale_items.product_id')
            ->orderBy('total_qty', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }
}