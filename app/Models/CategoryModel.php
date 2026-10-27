<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = ['name', 'slug', 'description', 'sort_order', 'is_active'];
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $validationRules  = [
        'name'        => 'required|min_length[2]|max_length[100]|is_unique[categories.name,id,{id}]',
        'slug'        => 'required|min_length[2]|max_length[120]|alpha_dash|is_unique[categories.slug,id,{id}]',
        'sort_order'  => 'permit_empty|integer|greater_than_equal_to[0]',
        'is_active'   => 'in_list[0,1]',
    ];
    protected $validationMessages = [
        'name' => [
            'required'   => 'Category.nameRequired',
            'min_length' => 'Category.nameLength',
            'max_length' => 'Category.nameLength',
            'is_unique'  => 'Category.nameDuplicate',
        ],
        'slug' => [
            'required'   => 'Category.slugRequired',
            'alpha_dash' => 'Category.slugInvalid',
            'is_unique'  => 'Category.slugDuplicate',
        ],
    ];
    protected $skipValidation   = false;

    public function getActive(int $limit = 0): array
    {
        $builder = $this->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC');
        if ($limit > 0) {
            $builder->limit($limit);
        }
        return $builder->findAll();
    }

    public function getDropdownOptions(): array
    {
        $categories = $this->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
        $options = [];
        foreach ($categories as $cat) {
            $options[$cat->id] = $cat->name;
        }
        return $options;
    }
}