<!doctype html>
<html lang="<?= esc(config('App')->defaultLocale) ?>">
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="bg-white text-dark">
    <main class="container py-4">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('partials/scripts') ?>
    <?= $this->renderSection('pageScripts') ?>
</body>
</html>
