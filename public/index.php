<?php
/**
 * Index - Entry Point
 * Facilita Cred - Loan Management System
 */

// Carrega as configurações
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Carrega as classes core
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Session.php';
require_once CORE_PATH . '/Router.php';

// Carrega helpers
require_once SHARED_PATH . '/helpers/functions.php';

// Inicia a sessão
Session::start();

// Cria o router
$router = new Router();

// ============================================
// ROTAS PÚBLICAS (sem autenticação)
// ============================================

// Login
$router->get('/', function() {
    if (Session::isAuthenticated()) {
        Router::redirect(Router::url('/dashboard'));
    }
    require FEATURES_PATH . '/auth/login-view.php';
});

$router->get('/login', function() {
    if (Session::isAuthenticated()) {
        Router::redirect(Router::url('/dashboard'));
    }
    require FEATURES_PATH . '/auth/login-view.php';
});

$router->post('/login', function() {
    require FEATURES_PATH . '/auth/login-action.php';
});

$router->get('/logout', function() {
    require FEATURES_PATH . '/auth/logout-action.php';
});

// ============================================
// ROTAS PROTEGIDAS (requerem autenticação)
// ============================================

// Middleware de autenticação
function requireAuth() {
    if (!Session::isAuthenticated()) {
        Session::flash('error', 'Você precisa estar autenticado para acessar esta página.');
        Router::redirect(Router::url('/login'));
    }
}

// Dashboard
$router->get('/dashboard', function() {
    requireAuth();
    require FEATURES_PATH . '/reports/dashboard-view.php';
});

// Carteiras
$router->get('/wallets', function() {
    requireAuth();
    require FEATURES_PATH . '/wallets/list-view.php';
});

$router->post('/wallets/create', function() {
    requireAuth();
    require FEATURES_PATH . '/wallets/wallet-actions.php';
});

$router->post('/wallets/edit/:id', function($id) {
    requireAuth();
    $_POST['action'] = 'edit';
    $_POST['wallet_id'] = $id;
    require FEATURES_PATH . '/wallets/wallet-actions.php';
});

$router->get('/wallets/:id', function($id) {
    requireAuth();
    $_GET['id'] = $id;
    require FEATURES_PATH . '/wallets/details-view.php';
});

// Clientes
$router->get('/clients', function() {
    requireAuth();
    require FEATURES_PATH . '/clients/list-view.php';
});

$router->post('/clients/create', function() {
    requireAuth();
    require FEATURES_PATH . '/clients/client-actions.php';
});

$router->post('/clients/edit/:id', function($id) {
    requireAuth();
    $_POST['action'] = 'edit';
    $_POST['client_id'] = $id;
    require FEATURES_PATH . '/clients/client-actions.php';
});

$router->get('/clients/:id', function($id) {
    requireAuth();
    $_GET['id'] = $id;
    require FEATURES_PATH . '/clients/details-view.php';
});

// Empréstimos
$router->get('/loans', function() {
    requireAuth();
    require FEATURES_PATH . '/loans/list-view.php';
});

$router->get('/loans/create', function() {
    requireAuth();
    require FEATURES_PATH . '/loans/create-view.php';
});

$router->post('/loans/create', function() {
    requireAuth();
    require FEATURES_PATH . '/loans/loan-actions.php';
});

$router->get('/loans/:id', function($id) {
    requireAuth();
    $_GET['id'] = $id;
    require FEATURES_PATH . '/loans/details-view.php';
});

$router->post('/loans/:id/pay', function($id) {
    requireAuth();
    $_POST['loan_id'] = $id;
    require FEATURES_PATH . '/loans/payment-action.php';
});

// Relatórios
$router->get('/reports/cash-flow', function() {
    requireAuth();
    require FEATURES_PATH . '/reports/cash-flow-view.php';
});

$router->get('/reports/profit', function() {
    requireAuth();
    require FEATURES_PATH . '/reports/profit-view.php';
});

// ============================================
// ROTA NÃO ENCONTRADA
// ============================================
$router->notFound(function() {
    http_response_code(404);
    echo "<h1>404 - Página não encontrada</h1>";
});

// Executa o roteamento
$router->run();
