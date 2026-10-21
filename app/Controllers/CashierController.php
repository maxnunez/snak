<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Placeholder landing for the staff area. Authorization lives in StaffFilter;
 * this controller never checks a role.
 */
class CashierController extends BaseController
{
    public function index(): string
    {
        return view('cashier/index', [
            'user' => service('auth')->currentUser(),
        ]);
    }
}
