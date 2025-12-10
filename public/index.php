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
require_once CORE_PATH . '/ErrorHandler.php';

// Carrega helpers
require_once SHARED_PATH . '/helpers/functions.php';

// Registra handler de erros
ErrorHandler::register();

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
    require FEATURES_PATH . '/wallets/create-action.php';
});

$router->post('/wallets/update', function() {
    requireAuth();
    require FEATURES_PATH . '/wallets/update-action.php';
});

$router->get('/wallets/delete', function() {
    requireAuth();
    require FEATURES_PATH . '/wallets/delete-action.php';
});

$router->post('/wallets/transaction', function() {
    requireAuth();
    require FEATURES_PATH . '/wallets/transaction-action.php';
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
    require FEATURES_PATH . '/clients/create-action.php';
});

$router->post('/clients/update', function() {
    requireAuth();
    require FEATURES_PATH . '/clients/update-action.php';
});

$router->get('/clients/delete', function() {
    requireAuth();
    require FEATURES_PATH . '/clients/delete-action.php';
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
    require FEATURES_PATH . '/loans/create-action.php';
});

$router->post('/loans/pay', function() {
    requireAuth();
    require FEATURES_PATH . '/loans/payment-action.php';
});

$router->get('/loans/:id', function($id) {
    requireAuth();
    $_GET['id'] = $id;
    require FEATURES_PATH . '/loans/details-view.php';
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
