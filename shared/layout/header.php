<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="<?= APP_NAME ?> - Sistema de Gestão de Empréstimos">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= APP_NAME ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">

    <!-- Favicon (opcional) -->
    <link rel="icon" type="image/x-icon" href="<?= ASSETS_URL ?>/images/favicon.ico">
</head>
<body>
    <?php if (Session::isAuthenticated()): ?>
        <div class="app-container">
            <?php require_once SHARED_PATH . '/layout/sidebar.php'; ?>
            <main class="main-content">
    <?php endif; ?>
