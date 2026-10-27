<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\SaleModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminController extends BaseController
{
    public function dashboard(): ResponseInterface|string
    {
        $user = service('auth')->currentUser();

        // Stats for dashboard
        $totalProducts = model(ProductModel::class)
            ->where('is_active', 1)
            ->countAllResults();

        $lowStock = model(ProductModel::class)
            ->where('is_active', 1)
            ->where('stock <= min_stock')
            ->countAllResults();

        $today = date('Y-m-d');
        $todaySales = model(SaleModel::class)
            ->where('DATE(created_at)', $today)
            ->where('status', 'completed')
            ->countAllResults();

        $todayRevenue = model(SaleModel::class)
            ->selectSum('total_amount')
            ->where('DATE(created_at)', $today)
            ->where('status', 'completed')
            ->first();

        $lowStockProducts = model(ProductModel::class)
            ->where('is_active', 1)
            ->where('stock <= min_stock')
            ->orderBy('stock', 'ASC')
            ->limit(10)
            ->findAll();

        $recentSales = model(SaleModel::class)
            ->select('sales.*, users.username as cashier_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->orderBy('sales.created_at', 'DESC')
            ->limit(10)
            ->findAll();

        return view('admin/dashboard', [
            'user'           => $user,
            'stats'          => [
                'total_products' => $totalProducts,
                'low_stock'      => $lowStock,
                'today_revenue'  => (float)($todayRevenue->total_amount ?? 0),
                'today_sales'    => $todaySales,
            ],
            'lowStock'       => $lowStockProducts,
            'recentSales'    => $recentSales,
        ]);
    }
}