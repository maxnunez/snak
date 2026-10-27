<!doctype html>
<html lang="<?= esc(config('App')->defaultLocale) ?>">
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand-md navbar-dark" style="background-color:#003353;">
        <div class="container">
            <a href="/cashier" class="navbar-brand">
                <i class="fas fa-utensils mr-2"></i>
                <strong><?= esc(lang('App.appName')) ?></strong>
            </a>

            <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <?php $user = service('auth')->currentUser() ?? null; ?>
                <?php if ($user) { ?>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a href="/cashier" class="nav-link">
                            <i class="fas fa-home mr-1"></i> <?= lang('Cashier.home') ?>
                        </a>
                    </li>
                    <?php if ($user->role === 'admin') { ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-cog mr-1"></i> Admin
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <a href="/admin/dashboard" class="dropdown-item">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="/admin/products" class="dropdown-item">
                                <i class="fas fa-box mr-2"></i> <?= lang('Cashier.products') ?>
                            </a>
                            <a href="/admin/categories" class="dropdown-item">
                                <i class="fas fa-tags mr-2"></i> <?= lang('Cashier.categories') ?>
                            </a>
                            <a href="/admin/sales" class="dropdown-item">
                                <i class="fas fa-chart-line mr-2"></i> <?= lang('Cashier.sales') ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/sales/create" class="dropdown-item">
                                <i class="fas fa-plus mr-2"></i> <?= lang('Sale.createTitle') ?>
                            </a>
                        </div>
                    </li>
                    <?php } ?>
                </ul>
                <ul class="navbar-nav ml-2">
                    <li class="nav-item">
                        <form method="post" action="/logout" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="nav-link btn-navbar" title="<?= lang('Auth.logoutButton') ?>">
                                <i class="fas fa-sign-out-alt mr-1"></i> <?= lang('Auth.logoutButton') ?>
                            </button>
                        </form>
                    </li>
                </ul>
                <?php } ?>
            </div>
        </div>
    </nav>
    <!-- /.navbar -->

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container pt-4">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="float-right d-none d-sm-inline">
                v1.0.0
            </div>
            <strong>&copy; <?= date('Y') ?> <?= esc(lang('App.appName')) ?> — UAB.</strong>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<?= $this->include('partials/scripts') ?>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
