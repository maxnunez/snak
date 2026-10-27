<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\SaleModel;
use App\Models\SaleItemModel;
use CodeIgniter\HTTP\ResponseInterface;

class SalesController extends BaseController
{
    public function index(): ResponseInterface|string
    {
        $page     = (int) ($this->request->getGet('page') ?? 1);
        $perPage  = 20;
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to') ?? date('Y-m-d');
        $status   = $this->request->getGet('status') ?? '';

        $model = model(SaleModel::class);
        $builder = $model
            ->select('sales.*, users.username as cashier_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->where('DATE(sales.created_at) >=', $dateFrom)
            ->where('DATE(sales.created_at) <=', $dateTo);

        if ($status !== '') {
            $builder->where('sales.status', $status);
        }

        $builder->orderBy('sales.created_at', 'DESC');

        $sales = $model->paginate($perPage, 'default', $page);
        $pager = $model->pager;

        // Summary for the period
        $summary = $model
            ->select('COUNT(*) as total_sales, SUM(total_amount) as total_revenue')
            ->where('DATE(created_at) >=', $dateFrom)
            ->where('DATE(created_at) <=', $dateTo)
            ->where('status', 'completed')
            ->first();

        return view('sales/index', [
            'sales'    => $sales,
            'pager'    => $pager,
            'summary'  => $summary,
            'filters'  => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'status'    => $status,
            ],
        ]);
    }

    public function create(): ResponseInterface|string
    {
        $products = model(ProductModel::class)
            ->where('is_active', 1)
            ->where('stock >', 0)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('sales/create', [
            'products' => $products,
            'errors'   => session('errors') ?? [],
        ]);
    }

    public function store(): ResponseInterface
    {
        $rawItems = $this->request->getPost('items');

        // Items arrive as JSON string from the cart hidden field
        $items = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;

        if (empty($items) || !is_array($items)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['items' => lang('Sale.itemsRequired')]);
        }

        $user = service('auth')->currentUser();
        $totalAmount = 0;
        $saleItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $quantity  = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = model(ProductModel::class)->find($productId);

            if ($product === null || (int)$product->is_active !== 1) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', ['items' => lang('Sale.productNotFound')]);
            }

            if ((int)$product->stock < $quantity) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', ['items' => sprintf(lang('Sale.insufficientStock'), $product->name, $product->stock)]);
            }

            $unitPrice = (float) $product->price;
            $subtotal  = $unitPrice * $quantity;

            $saleItems[] = [
                'product_id' => $productId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'subtotal'   => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        if (empty($saleItems)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['items' => lang('Sale.noValidItems')]);
        }

        $paymentMethod = $this->request->getPost('payment_method') ?? 'cash';
        $notes         = trim((string) $this->request->getPost('notes'));

        // Create sale
        $saleId = model(SaleModel::class)->insert([
            'user_id'      => $user->id,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'status'       => 'completed',
            'notes'        => $notes,
        ]);

        // Create sale items and decrease stock
        foreach ($saleItems as $item) {
            $item['sale_id'] = $saleId;
            model(SaleItemModel::class)->insert($item);
            model(ProductModel::class)->decreaseStock($item['product_id'], $item['quantity']);
        }

        return redirect()->to('/admin/sales')
            ->with('success', lang('Sale.createdSuccess'));
    }

    public function show(int $id): ResponseInterface|string
    {
        $sale = model(SaleModel::class)->getWithItems($id);

        if ($sale === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Sale.notFound'));
        }

        return view('sales/show', [
            'sale' => $sale,
        ]);
    }

    public function dailyReport(): ResponseInterface|string
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');

        $summary = model(SaleModel::class)->getDailyReport($date);

        $sales = model(SaleModel::class)
            ->select('sales.*, users.username as cashier_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->where('DATE(sales.created_at)', $date)
            ->orderBy('sales.created_at', 'DESC')
            ->findAll();

        return view('sales/daily', [
            'summary' => $summary,
            'sales'   => $sales,
            'date'    => $date,
        ]);
    }

    public function topProducts(): ResponseInterface|string
    {
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to') ?? date('Y-m-d');
        $limit    = (int) ($this->request->getGet('limit') ?? 20);

        $topProducts = model(SaleModel::class)->getTopProducts($dateFrom, $dateTo, $limit);

        return view('sales/top_products', [
            'topProducts' => $topProducts,
            'filters'     => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'limit'     => $limit,
            ],
        ]);
    }
}