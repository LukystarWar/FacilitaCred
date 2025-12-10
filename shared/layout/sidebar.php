<?php
/**
 * Sidebar Navigation
 */

// Define a rota atual para highlight do menu
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$currentPath = str_replace('/FacilitaCred/public', '', $currentPath);
$currentPath = $currentPath ?: '/';

function isActive($path) {
    global $currentPath;
    if ($path === '/dashboard' && ($currentPath === '/' || $currentPath === '/dashboard')) {
        return 'active';
    }
    return strpos($currentPath ?? '', $path) === 0 && $path !== '/dashboard' ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="FacilitaCred Logo" style="width: 48px; height: 48px;">
            <div class="sidebar-brand" style="margin: 0;">
                <span style="font-weight: 300;">Facilita</span><span style="font-weight: 700; color: #11C76F;">Cred</span>
            </div>
        </div>
        <div style="font-size: 0.75rem; color: #9ca3af;">
            Sistema de Gestão Financeira
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= Router::url('/dashboard') ?>" class="sidebar-nav-item <?= isActive('/dashboard') ?>">
            <img src="<?= ASSETS_URL ?>/images/analise.png" alt="Dashboard" class="sidebar-icon">
            <span>Dashboard</span>
        </a>

        <a href="<?= Router::url('/wallets') ?>" class="sidebar-nav-item <?= isActive('/wallets') ?>">
            <img src="<?= ASSETS_URL ?>/images/carteira.png" alt="Carteiras" class="sidebar-icon">
            <span>Carteiras</span>
        </a>

        <a href="<?= Router::url('/clients') ?>" class="sidebar-nav-item <?= isActive('/clients') ?>">
            <img src="<?= ASSETS_URL ?>/images/cliente.png" alt="Clientes" class="sidebar-icon">
            <span>Clientes</span>
        </a>

        <a href="<?= Router::url('/loans') ?>" class="sidebar-nav-item <?= isActive('/loans') ?>">
            <img src="<?= ASSETS_URL ?>/images/emprestimos.png" alt="Empréstimos" class="sidebar-icon">
            <span>Empréstimos</span>
        </a>

        <a href="<?= Router::url('/reports/cash-flow') ?>" class="sidebar-nav-item <?= isActive('/reports') ?>">
            <img src="<?= ASSETS_URL ?>/images/relatorios.png" alt="Relatórios" class="sidebar-icon">
            <span>Relatórios</span>
        </a>

        <a href="<?= Router::url('/settings') ?>" class="sidebar-nav-item <?= isActive('/settings') ?>">
            <img src="<?= ASSETS_URL ?>/images/configuracoes.png" alt="Configurações" class="sidebar-icon">
            <span>Configurações</span>
        </a>

        <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <a href="<?= Router::url('/logout') ?>" class="sidebar-nav-item" style="color: #f87171;">
                <img src="<?= ASSETS_URL ?>/images/logout.png" alt="Sair" class="sidebar-icon">
                <span>Sair</span>
            </a>
        </div>
    </nav>

    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.875rem; color: #9ca3af;">
        <div>Usuário: <strong style="color: white;"><?= sanitize(Session::getUsername()) ?></strong></div>
    </div>
</aside>
