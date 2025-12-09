<?php
/**
 * Sidebar Navigation
 */

// Define a rota atual para highlight do menu
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = str_replace('/FacilitaCred/public', '', $currentPath);

function isActive($path) {
    global $currentPath;
    if ($path === '/dashboard' && ($currentPath === '/' || $currentPath === '/dashboard')) {
        return 'active';
    }
    return strpos($currentPath, $path) === 0 && $path !== '/dashboard' ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            💰 <?= APP_NAME ?>
        </div>
        <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
            v<?= APP_VERSION ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= Router::url('/dashboard') ?>" class="sidebar-nav-item <?= isActive('/dashboard') ?>">
            <span>📊</span>
            <span>Dashboard</span>
        </a>

        <a href="<?= Router::url('/wallets') ?>" class="sidebar-nav-item <?= isActive('/wallets') ?>">
            <span>👛</span>
            <span>Carteiras</span>
        </a>

        <a href="<?= Router::url('/clients') ?>" class="sidebar-nav-item <?= isActive('/clients') ?>">
            <span>👥</span>
            <span>Clientes</span>
        </a>

        <a href="<?= Router::url('/loans') ?>" class="sidebar-nav-item <?= isActive('/loans') ?>">
            <span>💵</span>
            <span>Empréstimos</span>
        </a>

        <a href="<?= Router::url('/reports/cash-flow') ?>" class="sidebar-nav-item <?= isActive('/reports') ?>">
            <span>📈</span>
            <span>Relatórios</span>
        </a>

        <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <a href="<?= Router::url('/logout') ?>" class="sidebar-nav-item" style="color: #f87171;">
                <span>🚪</span>
                <span>Sair</span>
            </a>
        </div>
    </nav>

    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.875rem; color: #9ca3af;">
        <div>Usuário: <strong style="color: white;"><?= sanitize(Session::getUsername()) ?></strong></div>
    </div>
</aside>
