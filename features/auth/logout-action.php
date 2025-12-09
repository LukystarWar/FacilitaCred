<?php
/**
 * Logout Action
 * Realiza o logout do usuário
 */

// Remove os dados do usuário da sessão
Session::clearUser();

// Ou destrói toda a sessão
Session::destroy();

// Redireciona para o login
redirectWithMessage(
    Router::url('/login'),
    'success',
    'Você saiu do sistema com sucesso.'
);
