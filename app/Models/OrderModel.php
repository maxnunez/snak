<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'student_name',
        'student_lastname',
        'student_code',
        'student_initials',
        'product_id',
        'status',
    ];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules  = [
        'student_name'     => 'required|min_length[2]|max_length[100]',
        'student_lastname' => 'required|min_length[2]|max_length[100]',
        'student_code'     => 'required|exact_length[4]|is_unique[orders.student_code,id,{id}]',
        'student_initials' => 'required|max_length[10]',
        'product_id'       => 'required|integer|greater_than[0]',
        'status'           => 'in_list[pending,completed,cancelled]',
    ];

    protected $validationMessages = [
        'student_name'     => [
            'required'   => 'Order.studentNameRequired',
            'min_length' => 'Order.studentNameMin',
            'max_length' => 'Order.studentNameMax',
        ],
        'student_lastname' => [
            'required'   => 'Order.studentLastnameRequired',
            'min_length' => 'Order.studentLastnameMin',
            'max_length' => 'Order.studentLastnameMax',
        ],
        'student_code'     => [
            'required'   => 'Order.studentCodeRequired',
            'exact_length' => 'Order.studentCodeLength',
            'is_unique'  => 'Order.studentCodeDuplicate',
        ],
        'student_initials' => [
            'required'   => 'Order.studentInitialsRequired',
            'max_length' => 'Order.studentInitialsMax',
        ],
        'product_id'       => [
            'required'  => 'Order.productRequired',
            'integer'   => 'Order.productInvalid',
            'greater_than' => 'Order.productInvalid',
        ],
    ];

    protected $skipValidation   = false;

    public function getPendingOrders(int $limit = 0, int $offset = 0): array
    {
        $builder = $this
            ->select('orders.*, products.name as product_name, products.price as product_price')
            ->join('products', 'products.id = orders.product_id', 'left')
            ->where('orders.status', 'pending')
            ->orderBy('orders.created_at', 'ASC');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResult();
    }

    public function getAllOrders(int $limit = 0, int $offset = 0): array
    {
        $builder = $this
            ->select('orders.*, products.name as product_name, products.price as product_price')
            ->join('products', 'products.id = orders.product_id', 'left')
            ->orderBy('orders.created_at', 'DESC');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResult();
    }
}
