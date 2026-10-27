<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class OrderController extends BaseController
{
    public function index(): ResponseInterface|string
    {
        $productModel = model(ProductModel::class);
        $products = $productModel
            ->where('is_active', 1)
            ->where('stock >', 0)
            ->orderBy('name', 'ASC')
            ->findAll();

        $orderModel = model(OrderModel::class);
        $orders = $orderModel->getAllOrders();

        return view('orders/index', [
            'products' => $products,
            'orders'   => $orders,
            'errors'   => session('errors') ?? [],
        ]);
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'student_name'     => 'required|min_length[2]|max_length[100]',
            'student_lastname' => 'required|min_length[2]|max_length[100]',
            'product_id'       => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost([
            'student_name',
            'student_lastname',
            'product_id',
        ]);

        // Verificar stock disponible
        $product = model(ProductModel::class)->find($post['product_id']);
        if ($product === null || (int)$product->stock <= 0) {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['product_id' => lang('Order.productOutOfStock')]);
        }

        // Generar código de 4 números único automáticamente
        $orderModel = model(OrderModel::class);
        do {
            $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($orderModel->where('student_code', $code)->first() !== null);

        // Generar iniciales automáticamente: primera letra del nombre y apellido + guion
        $nameInitial  = strtoupper(trim((string) $post['student_name'][0] ?? ''));
        $lastInitial  = strtoupper(trim((string) $post['student_lastname'][0] ?? ''));
        $initials     = $nameInitial . $lastInitial . '-';

        $data = [
            'student_name'     => $post['student_name'],
            'student_lastname' => $post['student_lastname'],
            'student_code'     => $code,
            'student_initials' => $initials,
            'product_id'       => $post['product_id'],
            'status'           => 'pending',
        ];

        $orderModel->insert($data);

        // Descontar stock del producto
        model(ProductModel::class)->decreaseStock($post['product_id'], 1);

        return redirect()->back()
            ->with('success', lang('Order.createdSuccess'));
    }

    public function complete(int $id): ResponseInterface
    {
        $orderModel = model(OrderModel::class);
        $order = $orderModel->find($id);

        if ($order === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Order.notFound'));
        }

        $orderModel->update($id, ['status' => 'completed']);

        return redirect()->to('/orders')
            ->with('success', lang('Order.completedSuccess'));
    }

    public function cancel(int $id): ResponseInterface
    {
        $orderModel = model(OrderModel::class);
        $order = $orderModel->find($id);

        if ($order === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(lang('Order.notFound'));
        }

        $orderModel->update($id, ['status' => 'cancelled']);

        return redirect()->to('/orders')
            ->with('success', lang('Order.cancelledSuccess'));
    }
}
