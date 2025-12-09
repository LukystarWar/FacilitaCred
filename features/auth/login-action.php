<?php
/**
 * Login Action
 * Processa o login do usuário
 */

require_once FEATURES_PATH . '/auth/auth-service.php';

if (!isPost()) {
    Router::redirect(Router::url('/login'));
}

$username = post('username', '');
$password = post('password', '');

$authService = new AuthService();
$result = $authService->login($username, $password);

if ($result['success']) {
    // Define os dados do usuário na sessão
    Session::setUser($result['user']['id'], $result['user']['username']);

    // Redireciona para o dashboard
    redirectWithMessage(
        Router::url('/dashboard'),
        'success',
        $result['message']
    );
} else {
    // Volta para o login com mensagem de erro
    redirectWithMessage(
        Router::url('/login'),
        'error',
        $result['message']
    );
}
