<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('health', 'Health::index');

$routes->get('login', 'AuthController::showLogin');
$routes->post('login', 'AuthController::login');
$routes->post('logout', 'AuthController::logout');

$routes->group('cashier', ['filter' => 'staff:cashier,admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'CashierController::index');
});

$routes->group('admin', ['filter' => 'staff:admin'], static function (RouteCollection $routes): void {
    // Dashboard
    $routes->get('dashboard', 'AdminController::dashboard');
    $routes->get('', 'AdminController::dashboard');

    // Categories
    $routes->get('categories', 'CategoryController::index');
    $routes->get('categories/create', 'CategoryController::create');
    $routes->post('categories/store', 'CategoryController::store');
    $routes->get('categories/edit/(:num)', 'CategoryController::edit/$1');
    $routes->post('categories/update/(:num)', 'CategoryController::update/$1');
    $routes->post('categories/delete/(:num)', 'CategoryController::delete/$1');

    // Products
    $routes->get('products', 'ProductController::index');
    $routes->get('products/create', 'ProductController::create');
    $routes->post('products/store', 'ProductController::store');
    $routes->get('products/edit/(:num)', 'ProductController::edit/$1');
    $routes->post('products/update/(:num)', 'ProductController::update/$1');
    $routes->post('products/delete/(:num)', 'ProductController::delete/$1');
    $routes->post('products/toggle-featured/(:num)', 'ProductController::toggleFeatured/$1');
    $routes->post('products/adjust-stock/(:num)', 'ProductController::adjustStock/$1');
    $routes->post('products/upload-image', 'ProductController::uploadImage');

    // Sales
    $routes->get('sales', 'SalesController::index');
    $routes->get('sales/create', 'SalesController::create');
    $routes->post('sales/store', 'SalesController::store');
    $routes->get('sales/show/(:num)', 'SalesController::show/$1');
    $routes->get('sales/daily-report', 'SalesController::dailyReport');
    $routes->get('sales/top-products', 'SalesController::topProducts');

    // Orders
    $routes->get('orders', 'OrderController::index');
    $routes->post('orders/store', 'OrderController::store');
    $routes->post('orders/complete/(:num)', 'OrderController::complete/$1');
    $routes->post('orders/cancel/(:num)', 'OrderController::cancel/$1');
});

// Rutas públicas (sin login) - Pedidos de snack para estudiantes
$routes->get('productos', 'ProductController::publicIndex');
$routes->post('pedidos/store', 'OrderController::store');